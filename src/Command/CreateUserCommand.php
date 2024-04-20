<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;
use App\Entity\User;
use App\Repository\UserRepository;

#[AsCommand(
    name: 'app:create-user',
    description: 'Creates a new user.',
    hidden: false
)]
class CreateUserCommand extends Command
{
    public function __construct(private UserRepository $userRepository)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Creates a new user.');
        $this->setHelp('This command allows you to create a new user.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // The header of the instruction.
        $output->writeln('Create an user.');
        $output->writeln('---------------');
        $output->writeln('');

        $helper = $this->getHelper('question');

        // Ask for the first name of the newly created user.
        $firstNameQuestion = new Question('Please enter the first name of the user: ');
        
        $firstName = $helper->ask($input, $output, $firstNameQuestion);

        // Ask for the last name of the newly created user.
        $lastNameQuestion = new Question('Please enter the last name of the user: ');
        
        $lastName = $helper->ask($input, $output, $lastNameQuestion);

        // Ask for the role of the newly created user.
        $roleQuestion = new ChoiceQuestion('Please enter the role of the user: ', ['user', 'admin'], 'user');
        $roleQuestion->setErrorMessage('The role %s is invalid.');
        
        $role = $helper->ask($input, $output, $roleQuestion);
        switch ($role)
        {
            case 'user': $role = [User::ROLE_USER]; break;
            case 'admin': $role = [User::ROLE_ADMIN]; break;
        }

        // Ask for the e-mail of the newly created user.
        $emailQuestion = new Question('Please enter the e-mail of the user: ');
        
        $email = $helper->ask($input, $output, $emailQuestion);

        // Ask for the password of the newly create user.
        $passwordQuestion = new Question('Please enter the password of the user: ');
        $passwordQuestion->setHidden(true);
        $passwordQuestion->setHiddenFallback(false);
        
        $password = $helper->ask($input, $output, $passwordQuestion);

        // Create a user with previously specified configuration.
        $this->userRepository->createUser($firstName, $lastName, $role, $email, $password);

        return Command::SUCCESS;
    }
}
