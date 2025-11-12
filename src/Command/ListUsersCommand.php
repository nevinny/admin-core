<?php
namespace Nevinny\AdminCoreBundle\Command;

use AdminCore\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'admin:user:list',
    description: 'List all users',
)]
class ListUsersCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $users = $this->entityManager->getRepository(User::class)->findAll();

        if (empty($users)) {
            $io->warning('No users found');
            return Command::SUCCESS;
        }

        $rows = [];
        foreach ($users as $user) {
            $rows[] = [
                $user->getId(),
                $user->getEmail(),
                implode(', ', $user->getRoles()),
            ];
        }

        $io->table(['ID', 'Email', 'Roles'], $rows);

        return Command::SUCCESS;
    }
}
