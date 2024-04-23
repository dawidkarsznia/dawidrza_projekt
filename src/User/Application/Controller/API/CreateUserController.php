<?php

namespace App\User\Application\Controller\API;

use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Application\Service\CreateUserService;
use App\User\Application\Service\SendEmailService;
use App\User\Application\Service\GenerateApiKeyService;
use App\User\Application\Service\GeneratePasswordService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

final class CreateUserController extends AbstractController
{
    private UserRepositoryInterface $userRepository;
    private CreateUserService $createUserService;
    private SendEmailService $sendEmailService;
    private GenerateApiKeyService $generateApiKeyService;
    private GeneratePasswordService $generatePasswordService;

    public function __construct(
        UserRepositoryInterface $userRepository,
        CreateUserService $createUserService,
        SendEmailService $sendEmailService,
        GenerateApiKeyService $generateApiKeyService,
        GeneratePasswordService $generatePasswordService
    )
    {
        $this->userRepository = $userRepository;
        $this->createUserService = $createUserService;
        $this->sendEmailService = $sendEmailService;
        $this->generateApiKeyService = $generateApiKeyService;
        $this->generatePasswordService = $generatePasswordService;
    }

    public function createUser(Request $request): JsonResponse
    {
        // Deny access to this function, if the user is not an administrator.
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);

        $jsonRequest = $request->toArray();

        $user = new User();

        $userRepresentation = $this->createUserService->handle(
            $user,
            $request['firstName'],
            $request['lastName'],
            $request['email'],
            'ROLE_USER'
        );

        $plainPassword = $this->generatePasswordService->handle($user);

        $this->generateApiKeyService->handle($user);

        $this->userRepository->saveUser($user);

        // TODO: Send the newly generated password via e-mail.
        /* $this->sendEmailService->handle(
            $input->getArgument('email'),
            'Newly created password to be changed.',
            sprintf('%s', $plainPassword)
        ); */

        return JsonResponse::fromJsonString('');
    }
}