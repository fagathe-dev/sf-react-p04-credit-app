<?php

declare(strict_types=1);

namespace App\Crm\Priority;

use App\Entity\Dossier;
use App\Enum\CallbackPriorityEnum;
use Credit\Core\Enum\CallbackStatusEnum;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * Priorité d'appel d'un dossier, calculée à la lecture (elle dépend de l'heure).
 *
 * Garde commune : dossier actif, consentement de rappel donné, suivi d'appel encore ouvert.
 * Ensuite, la première règle qui correspond, dans l'ordre P1, P1 bis, P2, P3, l'emporte.
 */
final class CallbackPriorityResolver
{
    /** Suivis terminés : le dossier sort de la priorisation. */
    private const array CLOSED_CALLBACK_STATUSES = [CallbackStatusEnum::ADJUSTED, CallbackStatusEnum::DECLINED, CallbackStatusEnum::CLOSED];

    /** @var list<CallbackPriorityRuleInterface> */
    private array $rules;

    /**
     * @param iterable<CallbackPriorityRuleInterface> $rules
     */
    public function __construct(
        #[AutowireIterator(CallbackPriorityRuleInterface::TAG)]
        iterable $rules,
    ) {
        $rules = [...$rules];
        usort($rules, static fn (CallbackPriorityRuleInterface $a, CallbackPriorityRuleInterface $b): int => $a->getPriority()->rank() <=> $b->getPriority()->rank());
        $this->rules = $rules;
    }

    public function assess(Dossier $dossier, \DateTimeImmutable $now): PriorityAssessment
    {
        if (!$dossier->isActive()) {
            return new PriorityAssessment(CallbackPriorityEnum::NONE, 'Dossier en attente de vérification par le client.');
        }

        if (null === $dossier->getConsentAdvisorCallbackAt()) {
            return new PriorityAssessment(CallbackPriorityEnum::NONE, 'Pas de consentement de rappel : e-mails uniquement.');
        }

        if (\in_array($dossier->getCallbackStatus(), self::CLOSED_CALLBACK_STATUSES, true)) {
            return new PriorityAssessment(CallbackPriorityEnum::NONE, 'Suivi d\'appel terminé.');
        }

        $metrics = DossierMetrics::fromDossier($dossier);
        foreach ($this->rules as $rule) {
            if ($rule->matches($metrics, $now)) {
                return new PriorityAssessment($rule->getPriority(), $rule->describe($metrics, $now));
            }
        }

        return new PriorityAssessment(CallbackPriorityEnum::NONE, 'Aucune règle de relance ne s\'applique pour le moment.');
    }
}
