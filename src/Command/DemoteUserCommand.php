<?php
namespace AdminCore\Command;

use AdminCore\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'admin:user:demote',
    description: 'Remove role from user',
)]
class DemoteUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'User email')
            ->addArgument('role', InputArgument::REQUIRED, 'Role to remove');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = $input->getArgument('email');
        $role = strtoupper($input->getArgument('role'));

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user) {
            $io->error('User not found!');
            return Command::FAILURE;
        }

        $roles = $user->getRoles();
        if (!in_array($role, $roles)) {
            $io->warning('User does not have this role!');
            return Command::SUCCESS;
        }

        $roles = array_diff($roles, [$role]);
        $user->setRoles(array_values($roles));

        $this->entityManager->flush();

        $io->success(sprintf('Role "%s" removed from user "%s"', $role, $email));

        return Command::SUCCESS;
    }
}
