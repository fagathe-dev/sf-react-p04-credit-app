<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Advisor;
use App\Repository\AdvisorRepository;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Création d'un conseiller (pas d'inscription publique) : il se connecte ensuite par code OTP.
 */
#[AsCommand(name: 'app:advisor:create', description: 'Crée un conseiller du back-office credit_crm.')]
final readonly class CreateAdvisorCommand
{
    public function __construct(
        private AdvisorRepository $advisorRepository,
        private ValidatorInterface $validator,
    ) {}

    public function __invoke(
        SymfonyStyle $io,
        #[Argument(description: 'Adresse e-mail du conseiller')] string $email,
        #[Argument(description: 'Prénom')] string $firstName,
        #[Argument(description: 'Nom')] string $lastName,
    ): int {
        if (\count($this->validator->validate($email, new Email())) > 0) {
            $io->error(\sprintf('Adresse e-mail invalide : %s', $email));

            return Command::INVALID;
        }

        if (null !== $this->advisorRepository->findOneByEmail($email)) {
            $io->error(\sprintf('Un conseiller existe déjà avec l\'adresse %s.', $email));

            return Command::FAILURE;
        }

        $advisor = new Advisor($email, trim($firstName), trim($lastName));
        $this->advisorRepository->save($advisor);

        $io->success(\sprintf('Conseiller %s créé (%s). Il se connecte depuis credit_crm avec un code reçu par e-mail.', $advisor->getFullName(), $advisor->getEmail()));

        return Command::SUCCESS;
    }
}
