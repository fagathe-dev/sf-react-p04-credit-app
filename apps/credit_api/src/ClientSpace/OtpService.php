<?php

declare(strict_types=1);

namespace App\ClientSpace;

use App\Entity\Advisor;
use App\Entity\Client;
use App\Entity\Dossier;
use App\Entity\OtpCode;
use App\Mailer\OtpCodeMailDto;
use App\Mailer\OtpCodeMailHandler;
use App\Repository\OtpCodeRepository;
use Fagathe\CorePhp\Enum\LoggerLevelEnum;
use Fagathe\CorePhp\Trait\LoggerTrait;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Génère, envoie par e-mail et vérifie les codes OTP (clients et conseillers).
 *
 * 6 chiffres, usage unique, stockés hachés (HMAC-SHA256 avec APP_SECRET), validité OTP_TTL_MINUTES,
 * OTP_MAX_ATTEMPTS essais, un nouveau code invalide les précédents, un envoi par OTP_RESEND_DELAY_SECONDS.
 */
final class OtpService
{
    use LoggerTrait;

    public function __construct(
        private readonly OtpCodeRepository $otpCodeRepository,
        private readonly OtpCodeMailHandler $mailHandler,
        #[Autowire(param: 'kernel.secret')]
        private readonly string $secret,
        #[Autowire(env: 'int:OTP_TTL_MINUTES')]
        private readonly int $ttlMinutes,
        #[Autowire(env: 'int:OTP_MAX_ATTEMPTS')]
        private readonly int $maxAttempts,
        #[Autowire(env: 'int:OTP_RESEND_DELAY_SECONDS')]
        private readonly int $resendDelaySeconds,
    ) {}

    /**
     * Émet un code et l'envoie par e-mail.
     *
     * @return bool faux si le délai de renvoi n'est pas écoulé (aucun code émis)
     */
    public function issue(Client|Advisor $owner, string $loginUrl, ?Dossier $dossier = null, bool $enforceResendDelay = true): bool
    {
        $now = new \DateTimeImmutable();

        if ($enforceResendDelay && null !== ($latest = $this->otpCodeRepository->findLatest($owner))
            && $latest->getCreatedAt() > $now->modify(\sprintf('-%d seconds', $this->resendDelaySeconds))) {
            return false;
        }

        foreach ($this->otpCodeRepository->findUsable($owner, $now) as $previous) {
            $previous->invalidate();
        }

        $code = str_pad((string) random_int(0, 999_999), 6, '0', STR_PAD_LEFT);
        $expiresAt = $now->modify(\sprintf('+%d minutes', $this->ttlMinutes));

        $otp = $owner instanceof Client
            ? OtpCode::forClient($owner, $this->hash($code), $expiresAt, $dossier)
            : OtpCode::forAdvisor($owner, $this->hash($code), $expiresAt);

        $this->otpCodeRepository->save($otp);

        try {
            $this->mailHandler->send(new OtpCodeMailDto(
                recipientEmail: $owner->getEmail(),
                recipientName: $owner->getFullName(),
                code: $code,
                ttlMinutes: $this->ttlMinutes,
                loginUrl: $loginUrl,
            ));
        } catch (\Throwable $e) {
            // Le code reste valable : la personne peut demander un renvoi
            $this->generateLog(LoggerLevelEnum::Error, [
                'message' => 'Échec de l\'envoi du code OTP.',
                'data' => ['otp' => $otp->getId()->toRfc4122(), 'owner' => $owner->getId()->toRfc4122()],
                'exception' => $e,
            ], ['action' => 'otp.send_failed']);
        }

        $this->generateLog(LoggerLevelEnum::Info, [
            'message' => 'Code OTP émis.',
            'data' => ['otp' => $otp->getId()->toRfc4122(), 'owner' => $owner->getId()->toRfc4122(), 'dossier' => $dossier?->getId()->toRfc4122()],
        ], ['action' => 'otp.issued']);

        return true;
    }

    /**
     * @return array{0: OtpVerificationStatusEnum, 1: OtpCode|null}
     */
    public function verify(Client|Advisor $owner, string $code): array
    {
        $otp = $this->otpCodeRepository->findLatestUsable($owner, new \DateTimeImmutable());

        if (null === $otp) {
            return [OtpVerificationStatusEnum::INVALID, null];
        }

        if ($otp->getAttempts() >= $this->maxAttempts) {
            $this->otpCodeRepository->save($otp->invalidate());

            return [OtpVerificationStatusEnum::TOO_MANY_ATTEMPTS, null];
        }

        if (!hash_equals($otp->getCodeHash(), $this->hash($code))) {
            $otp->incrementAttempts();

            if ($otp->getAttempts() >= $this->maxAttempts) {
                $otp->invalidate();
                $this->otpCodeRepository->save($otp);

                return [OtpVerificationStatusEnum::TOO_MANY_ATTEMPTS, null];
            }

            $this->otpCodeRepository->save($otp);

            return [OtpVerificationStatusEnum::INVALID, null];
        }

        $this->otpCodeRepository->save($otp->consume());

        return [OtpVerificationStatusEnum::VALID, $otp];
    }

    private function hash(string $code): string
    {
        return hash_hmac('sha256', $code, $this->secret);
    }
}
