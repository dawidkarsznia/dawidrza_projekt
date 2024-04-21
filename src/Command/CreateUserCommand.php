<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Validator\UserValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\ByteString;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Console\Exception\RuntimeException;

use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;


#[AsCommand(
    name: 'app:create-user',
    description: 'Creates a new user.',
    hidden: false
)]
class CreateUserCommand extends Command
{
    private SymfonyStyle $io;

    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private UserValidator $userValidator
        )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp('This command creates a user with specified parameters.');

        $this->addArgument('firstName', InputArgument::OPTIONAL, 'The first name of the user.');
        $this->addArgument('lastName', InputArgument::OPTIONAL, 'The first name of the user.');
        $this->addArgument('password', InputArgument::OPTIONAL, 'The password of the user.');
        $this->addArgument('email', InputArgument::OPTIONAL, 'The email of the user.');
        $this->addOption('admin', null, InputOption::VALUE_NONE, 'Whether the user is an administrator.');
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        // SymfonyStyle is an optional feature that Symfony provides so you can
        // apply a consistent look to the commands of your application.
        // See https://symfony.com/doc/current/console/style.html
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        // Use the provided first name or ask the user to give it.
        $firstName = $input->getArgument('firstName');
        if (null === $firstName)
        {
            $firstName = $this->io->ask('First name:', null);
            $input->setArgument('firstName', $firstName);
        }

        // Use the provided last name or ask the user to give it.
        $lastName = $input->getArgument('lastName');
        if (null === $lastName)
        {
            $lastName = $this->io->ask('Last name:', null);
            $input->setArgument('lastName', $lastName);
        }

        // Use the provided e-mail or ask the user to give it.
        $email = $input->getArgument('email');
        if (null === $email)
        {
            $email = $this->io->ask('E-mail:', null);
            $input->setArgument('email', $email);
        }

        // Use the provided password or ask the user to give it.
        $password = $input->getArgument('password');
        if (null === $password)
        {
            $password = $this->io->askHidden('Password:', null);
            $input->setArgument('password', $password);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Get the arguments provided.
        $firstName = $input->getArgument('firstName');
        $lastName = $input->getArgument('lastName');
        $email = $input->getArgument('email');
        $password = $input->getArgument('password');
        $isAdmin = $input->getOption('admin');
        $generatedApiKey = ByteString::fromRandom(32)->toString();

        // make sure to validate the user data is correct
        $this->validateUserData($firstName, $lastName, $password, $email);

        // Create the user.
        $user = new User();
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setEmail($email);
        $user->setRoles([$isAdmin ? User::ROLE_ADMIN : User::ROLE_USER]);
        $user->setActive(true);
        
        // Check whether the API key generated doesn't already exists.
        while (null !== $this->userRepository->findOneBy(['apiKey' => $generatedApiKey]))
        {
            $generatedApiKey = ByteString::fromRandom(32)->toString();
        }
        $user->setApiKey($generatedApiKey);

        // Hash the password.
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        // Add the user to the database.
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->io->success(sprintf('%s was successfully created: %s %s (%s)', $isAdmin ? 'Administrator user' : 'User', $user->getFirstName(), $user->getLastName(), $user->getEmail()));

        return Command::SUCCESS;
    }

    private function validateUserData(string $firstName, string $lastName, string $password, string $email): void
    {
        // We check whether the user with given e-mail already exists
        $existingUser = $this->userRepository->findOneBy(['email' => $email]);
        if (null !== $existingUser) {
            throw new RuntimeException(sprintf('There user with the "%s" e-mail already exists.', $email));
        }

        // Validate the given fields (if case they were not added interactively).
        $this->userValidator->validateName($firstName);
        $this->userValidator->validateName($lastName);
        $this->userValidator->validateEmail($email);
        $this->userValidator->validatePassword($password);
    }
}
