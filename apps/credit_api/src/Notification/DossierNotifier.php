<?php

declare(strict_types=1);

namespace App\Notification;

use App\Entity\Dossier;
use App\Entity\EmailNotification;
use App\Enum\EmailNotificationStatusEnum;
use App\Enum\EmailNotificationTypeEnum;
use App\Notification\Handler\DossierMailHandlerInterface;
use App\Repository\EmailNotificationRepository;
use Credit\Core\Enum\ScoringDecisionEnum;
use Fagathe\CorePhp\Enum\LoggerLevelEnum;
use Fagathe\CorePhp\Trait\LoggerTrait;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * Envoie un e-mail de dossier et le trace dans email_notification (envoyé ou en échec).
 * Un type déjà envoyé avec succès pour un dossier n'est jamais renvoyé ; un échec sera retenté.
 */
final class DossierNotifier
{
    use LoggerTrait;

    /** @var array<string, DossierMailHandlerInterface> */
    private array $handlers = [];

    /**
     * @param iterable<DossierMailHandlerInterface> $handlers
     */
    public function __construct(
        #[AutowireIterator(DossierMailHandlerInterface::TAG)]
        iterable $handlers,
        private readonly DossierMailFactory $mailFactory,
        private readonly EmailNotificationRepository $notificationRepository,
    ) {
        foreach ($handlers as $handler) {
            $this->handlers[$handler->getType()->value] = $handler;
        }
    }

    /**
     * Dossier visible dans l'espace client : confirmation, puis transmission aux partenaires
     * (sauf refus ferme : rien n'est transmis).
     */
    public function notifyDossierRegistered(Dossier $dossier): void
    {
        $this->notify($dossier, EmailNotificationTypeEnum::REQUEST_CONFIRMATION);

        if (ScoringDecisionEnum::HARD_REJECT !== $dossier->getDecision()) {
            $this->notify($dossier, EmailNotificationTypeEnum::PARTNER_TRANSMISSION);
        }
    }

    public function notify(Dossier $dossier, EmailNotificationTypeEnum $type, ?\DateTimeImmutable $now = null): ?EmailNotification
    {
        if ($this->notificationRepository->existsSentFor($dossier, $type)) {
            return null;
        }

        $handler = $this->handlers[$type->value] ?? throw new \LogicException(\sprintf('Aucun e-mail défini pour le type %s.', $type->value));
        $dto = $this->mailFactory->build($dossier);
        $status = EmailNotificationStatusEnum::SENT;
        $error = null;

        try {
            $handler->send($dto);
        } catch (\Throwable $e) {
            $status = EmailNotificationStatusEnum::FAILED;
            $error = $e->getMessage();
            $this->generateLog(LoggerLevelEnum::Error, [
                'message' => 'Échec d\'un e-mail de dossier.',
                'data' => ['dossier' => $dossier->getId()->toRfc4122(), 'type' => $type->value],
                'exception' => $e,
            ], ['action' => 'dossier.mail_failed']);
        }

        $notification = new EmailNotification($dossier, $type, $dto->recipientEmail, $handler->subjectFor($dto), $status, $now, $error);
        $this->notificationRepository->save($notification);

        return $notification;
    }
}
