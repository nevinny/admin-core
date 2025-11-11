<?php
namespace AdminCore\Command;

use AdminCore\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'admin:user:create',
    description: 'Create a new user',
)]
class CreateUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::OPTIONAL, 'User email')
            ->addArgument('password', InputArgument::OPTIONAL, 'User password')
            ->addOption('admin', null, InputOption::VALUE_NONE, 'Set user as admin')
            ->addOption('super-admin', null, InputOption::VALUE_NONE, 'Set user as super admin');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $helper = $this->getHelper('question');

        // Email
        $email = $input->getArgument('email');
        if (!$email) {
            $emailQuestion = new Question('Email: ');
            $emailQuestion->setValidator(function ($value) {
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    throw new \RuntimeException('Invalid email format');
                }
                return $value;
            });
            $email = $helper->ask($input, $output, $emailQuestion);
        }

        // Проверка существования
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            $io->error('User with this email already exists!');
            return Command::FAILURE;
        }

        // Password
        $password = $input->getArgument('password');
        if (!$password) {
            $passwordQuestion = new Question('Password: ');
            $passwordQuestion->setHidden(true);
            $passwordQuestion->setHiddenFallback(false);
            $passwordQuestion->setValidator(function ($value) {
                if (strlen($value) < 6) {
                    throw new \RuntimeException('Password must be at least 6 characters');
                }
                return $value;
            });
            $password = $helper->ask($input, $output, $passwordQuestion);
        }

        // Roles
        $roles = ['ROLE_USER'];
        if ($input->getOption('super-admin')) {
            $roles[] = 'ROLE_SUPER_ADMIN';
        } elseif ($input->getOption('admin')) {
            $roles[] = 'ROLE_ADMIN';
        }

        // Создаем пользователя
        $user = new User();
        $user->setEmail($email);
        $user->setRoles($roles);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf('User "%s" created successfully with roles: %s', $email, implode(', ', $roles)));

        return Command::SUCCESS;
    }
}
