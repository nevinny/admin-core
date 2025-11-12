<?php
namespace Nevinny\AdminCoreBundle\Command;

use Nevinny\AdminCoreBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'admin:user:promote',
    description: 'Add role to user',
)]
class PromoteUserCommand extends Command
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
            ->addArgument('role', InputArgument::REQUIRED, 'Role to add (e.g., ROLE_ADMIN)');
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
        if (in_array($role, $roles)) {
            $io->warning('User already has this role!');
            return Command::SUCCESS;
        }

        $roles[] = $role;
        $user->setRoles($roles);

        $this->entityManager->flush();

        $io->success(sprintf('Role "%s" added to user "%s"', $role, $email));

        return Command::SUCCESS;
    }
}
