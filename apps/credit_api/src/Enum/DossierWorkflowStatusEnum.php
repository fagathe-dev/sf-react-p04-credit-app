<?php

declare(strict_types=1);

namespace App\Enum;

use Credit\Core\Enum\ScoringDecisionEnum;

/**
 * Avancement commercial d'un dossier (distinct de la décision de scoring, figée au résultat).
 *
 *   QUOTES_GENERATED ──(le client choisit une offre)──▶ QUOTE_SUBSCRIBED ──(conseiller)──▶ APPROVED | REJECTED
 *   NO_OFFER_ADVISOR_QUEUE : aucune offre telle quelle, pistes d'ajustement pour un conseiller
 *   REJECTED : refus ferme (HARD_REJECT) ou souscription refusée
 */
enum DossierWorkflowStatusEnum: string
{
    case QUOTES_GENERATED = 'QUOTES_GENERATED';
    case NO_OFFER_ADVISOR_QUEUE = 'NO_OFFER_ADVISOR_QUEUE';
    case QUOTE_SUBSCRIBED = 'QUOTE_SUBSCRIBED';
    case APPROVED = 'APPROVED';
    case REJECTED = 'REJECTED';

    /** Statut initial selon la décision du moteur de scoring. */
    public static function fromDecision(ScoringDecisionEnum $decision): self
    {
        return match ($decision) {
            ScoringDecisionEnum::INSTANT_OFFERS => self::QUOTES_GENERATED,
            ScoringDecisionEnum::ADVISOR_CALLBACK_NEEDED => self::NO_OFFER_ADVISOR_QUEUE,
            ScoringDecisionEnum::HARD_REJECT => self::REJECTED,
        };
    }

    /**
     * Transitions autorisées depuis ce statut par un conseiller (credit_crm).
     *
     * @return list<self>
     */
    public function advisorTransitions(): array
    {
        return match ($this) {
            self::QUOTE_SUBSCRIBED => [self::APPROVED, self::REJECTED],
            default => [],
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::QUOTES_GENERATED => 'Offres en attente de choix',
            self::NO_OFFER_ADVISOR_QUEUE => 'À ajuster avec un conseiller',
            self::QUOTE_SUBSCRIBED => 'Offre choisie, en cours de validation',
            self::APPROVED => 'Dossier accepté',
            self::REJECTED => 'Dossier refusé',
        };
    }
}
