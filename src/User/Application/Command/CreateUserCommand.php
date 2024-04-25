<?php

namespace App\User\Application\Command;

use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Application\Service\CreateUserService;
use App\User\Application\Service\GenerateApiKeyService;
use App\User\Application\Service\GeneratePasswordService;
use App\User\Application\Validation\UserValidator;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-user',
    description: 'Creates a new user.',
    hidden: false
)]
final class CreateUserCommand extends Command
{
    private UserRepositoryInterface $userRepository;
    private CreateUserService $createUserService;
    private UserValidator $userValidator;
    private GenerateApiKeyService $generateApiKeyService;
    private GeneratePasswordService $generatePasswordService;
    private SymfonyStyle $ioStyle;

    public function __construct(
        UserRepositoryInterface $userRepository,
        CreateUserService $createUserService,
        GenerateApiKeyService $generateApiKeyService,
        GeneratePasswordService $generatePasswordService,
        UserValidator $userValidator)
    {
        $this->userRepository = $userRepository;
        $this->createUserService = $createUserService;
        $this->generateApiKeyService = $generateApiKeyService;
        $this->generatePasswordService = $generatePasswordService;
        $this->userValidator = $userValidator;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp('This command creates a user with specified parameters.');

        $this->addArgument('firstName', InputArgument::OPTIONAL, 'The first name of the user.');
        $this->addArgument('lastName', InputArgument::OPTIONAL, 'The last name of the user.');
        $this->addArgument('email', InputArgument::OPTIONAL, 'The e-mail of the user.');
        $this->addOption('admin', null, InputOption::VALUE_NONE, 'Whether the user is an administrator.');
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        // SymfonyStyle is an optional feature that Symfony provides so you can
        // apply a consistent look to the commands of your application.
        // See https://symfony.com/doc/current/console/style.html
        $this->ioStyle = new SymfonyStyle($input, $output);
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $firstName = $input->getArgument('firstName');
        if (null === $firstName)
        {
            $firstName = $this->ioStyle->ask('First name:', null);
            $input->setArgument('firstName', $firstName);
        }

        $lastName = $input->getArgument('lastName');
        if (null === $lastName)
        {
            $lastName = $this->ioStyle->ask('Last name:', null);
            $input->setArgument('lastName', $lastName);
        }

        $email = $input->getArgument('email');
        if (null === $email)
        {
            $email = $this->ioStyle->ask('E-mail:', null);
            $input->setArgument('email', $email);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {   
        $isAdmin = $input->getOption('admin');

        if (!$this->userValidator->validateUserFirstName($input->getArgument('firstName')))
        {
            $this->ioStyle->error('The first name provided is not valid.');
            return Command::FAILURE;
        }

        if (!$this->userValidator->validateUserLastName($input->getArgument('lastName')))
        {
            $this->ioStyle->error('The last name provided is not valid.');
            return Command::FAILURE;
        }

        if (!$this->userValidator->validateUserEmail($input->getArgument('email')))
        {
            $this->ioStyle->error('The e-mail provided is not valid.');
            return Command::FAILURE;
        }

        $user = new User();

        $userRepresentation = $this->createUserService->handle(
            $user,
            $input->getArgument('firstName'),
            $input->getArgument('lastName'),
            $input->getArgument('email'),
            [($isAdmin) ? 'ROLE_ADMIN' : 'ROLE_USER']
        );

        $plainPassword = $this->generatePasswordService->handle($user);

        $this->generateApiKeyService->handle($user);

        $this->userRepository->saveUser($user);

        // For debugging.
        $output->writeln($plainPassword);

        $this->ioStyle->success(sprintf('%s was successfully created: %s %s (%s)',
            $isAdmin ? 'Administrator user' : 'User',
            $input->getArgument('firstName'),
            $input->getArgument('lastName'),
            $input->getArgument('email')
        ));

        return Command::SUCCESS;
    }
}