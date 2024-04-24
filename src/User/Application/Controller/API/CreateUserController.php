<?php

namespace App\User\Application\Controller\API;

use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Application\Service\CreateUserService;
use App\User\Application\Service\SendEmailService;
use App\User\Application\Service\GenerateApiKeyService;
use App\User\Application\Service\GeneratePasswordService;
use App\User\Application\ApiResponse\ApiResponseInterface;
use App\User\Application\Validation\UserValidator;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;


final class CreateUserController extends AbstractController
{
    private UserRepositoryInterface $userRepository;
    private CreateUserService $createUserService;
    private SendEmailService $sendEmailService;
    private GenerateApiKeyService $generateApiKeyService;
    private GeneratePasswordService $generatePasswordService;
    private ApiResponseInterface $apiResponseInterface;
    private UserValidator $userValidator;

    public function __construct(
        UserRepositoryInterface $userRepository,
        CreateUserService $createUserService,
        SendEmailService $sendEmailService,
        GenerateApiKeyService $generateApiKeyService,
        GeneratePasswordService $generatePasswordService,
        ApiResponseInterface $apiResponseInterface,
        UserValidator $userValidator
    )
    {
        $this->userRepository = $userRepository;
        $this->createUserService = $createUserService;
        $this->sendEmailService = $sendEmailService;
        $this->generateApiKeyService = $generateApiKeyService;
        $this->generatePasswordService = $generatePasswordService;
        $this->apiResponseInterface = $apiResponseInterface;
        $this->userValidator = $userValidator;
    }

    public function createUser(Request $request): JsonResponse
    {
        // Deny access to this function, if the user is not an administrator.
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);

        $requestData = $request->toArray();

        $user = new User();

        if (!$this->userValidator->validateUserFirstName($requestData['firstName']))
        {
            throw new BadRequestHttpException('The first name provided is not valid.');
        }

        if (!$this->userValidator->validateUserLastName($requestData['lastName']))
        {
            throw new BadRequestHttpException('The last name provided is not valid.');
        }

        if (!$this->userValidator->validateUserEmail($requestData['email']))
        {
            throw new BadRequestHttpException('The e-mail provided is not valid.');
        }

        $userRepresentation = $this->createUserService->handle(
            $user,
            $requestData['firstName'],
            $requestData['lastName'],
            $requestData['email'],
            []
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

        return $this->apiResponseInterface->createResponse('', 'success', Response::HTTP_OK);
    }
}