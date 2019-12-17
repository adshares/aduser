<?php

namespace Adshares\Aduser\Command;

use Adshares\Aduser\Entity\ApiKey;
use Adshares\Aduser\Security\UserProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CreateUserCommand extends Command
{
    protected static $defaultName = 'ops:user:create';

    private $userProvider;

    public function __construct(
        UserProvider $userProvider,
        string $name = null
    ) {
        parent::__construct($name);
        $this->userProvider = $userProvider;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Creates a new user')
            ->addArgument('email', InputArgument::OPTIONAL, 'User email address')
            ->addArgument('fullname', InputArgument::OPTIONAL, 'User full name')
            ->addArgument('password', InputArgument::OPTIONAL, 'User password')
            ->addOption('admin', null, InputOption::VALUE_NONE, 'Add ADMIN role')
            ->addOption('reviewer', null, InputOption::VALUE_NONE, 'Add REVIEWER role');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = $input->getArgument('email') ?? $io->ask('Email address');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('Invalid email address.');
        }

        $fullname = $input->getArgument('fullname') ?? $io->ask('Full name');
        if (empty($fullname)) {
            throw new \RuntimeException('Name cannot be empty.');
        }

        $password = str_replace(['+', '/', '='], ['x', 'y', ''], base64_encode(random_bytes(8)));
        $password = $input->getArgument('password') ?? $io->ask('Password', $password);
        if (empty($password)) {
            throw new \RuntimeException('Password cannot be empty.');
        }

        $role = 'CLIENT';
        if ($input->getOption('admin')) {
            $role = 'ADMIN';
        } elseif ($input->getOption('reviewer')) {
            $role = 'REVIEWER';
        }

        $user = $this->userProvider->createUser($email, $fullname, $password, ['ROLE_'.$role]);
        $keys = $user->getApiKeys();
        /** @var ApiKey $apiKey */
        $apiKey = reset($keys);

        $io->success(
            [
                sprintf('%s %s (%s) has been created', $role, $user->getFullName(), $user->getEmail()),
                sprintf('Password: %s', $password),
                sprintf('API key name: %s', $apiKey->getName()),
                sprintf('API key secret: %s', $apiKey->getSecret()),
            ]
        );

        return 0;
    }
}
