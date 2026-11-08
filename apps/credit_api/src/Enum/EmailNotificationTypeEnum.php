<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * E-mails transactionnels liés à un dossier (historique : table email_notification).
 */
enum EmailNotificationTypeEnum: string
{
    /** Confirmation de la demande, à l'enregistrement du dossier (ou à sa vérification par code). */
    case REQUEST_CONFIRMATION = 'REQUEST_CONFIRMATION';
    /** Transmission de la demande aux établissements partenaires du projet. */
    case PARTNER_TRANSMISSION = 'PARTNER_TRANSMISSION';
    /** Relance J+1 : offres non choisies 24 h après le résultat. */
    case REMINDER_D1 = 'REMINDER_D1';
    /** Relance J+7 « Votre devis expire bientôt » : envoyée entre J+5 et l'expiration (J+7). */
    case EXPIRY_REMINDER = 'EXPIRY_REMINDER';

    public function label(): string
    {
        return match ($this) {
            self::REQUEST_CONFIRMATION => 'Confirmation de la demande',
            self::PARTNER_TRANSMISSION => 'Transmission aux partenaires',
            self::REMINDER_D1 => 'Relance J+1',
            self::EXPIRY_REMINDER => 'Relance J+7 : devis bientôt expiré',
        };
    }
}
