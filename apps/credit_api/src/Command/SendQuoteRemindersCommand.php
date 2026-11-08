<?php

declare(strict_types=1);

namespace App\Command;

use App\Notification\QuoteReminderScheduler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Relances J+1 et J+7 des offres non choisies. À planifier (cron), par exemple toutes les heures :
 *   0 * * * * php /chemin/apps/credit_api/bin/console app:dossiers:send-reminders
 */
#[AsCommand(name: 'app:dossiers:send-reminders', description: 'Envoie les relances e-mail J+1 et J+7 des offres non choisies.')]
final readonly class SendQuoteRemindersCommand
{
    public function __construct(
        private QuoteReminderScheduler $scheduler,
    ) {}

    public function __invoke(SymfonyStyle $io): int
    {
        $counts = $this->scheduler->run(new \DateTimeImmutable());

        $io->success(\sprintf('%d relance(s) envoyée(s), %d en échec (retentées au prochain passage).', $counts['sent'], $counts['failed']));

        return 0 === $counts['failed'] ? Command::SUCCESS : Command::FAILURE;
    }
}
