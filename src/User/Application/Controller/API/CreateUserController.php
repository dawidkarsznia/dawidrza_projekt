<?php

namespace App\User\Application\Controller\API;

use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Application\Service\CreateUserService;
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
    private GenerateApiKeyService $generateApiKeyService;
    private GeneratePasswordService $generatePasswordService;
    private ApiResponseInterface $apiResponseInterface;
    private UserValidator $userValidator;

    public function __construct(
        UserRepositoryInterface $userRepository,
        CreateUserService $createUserService,
        GenerateApiKeyService $generateApiKeyService,
        GeneratePasswordService $generatePasswordService,
        ApiResponseInterface $apiResponseInterface,
        UserValidator $userValidator
    )
    {
        $this->userRepository = $userRepository;
        $this->createUserService = $createUserService;
        $this->generateApiKeyService = $generateApiKeyService;
        $this->generatePasswordService = $generatePasswordService;
        $this->apiResponseInterface = $apiResponseInterface;
        $this->userValidator = $userValidator;
    }

    public function createUser(Request $request): JsonResponse
    {
        // Deny access to this function, if the user is not an administrator.
        if (!$this->isGranted(User::ROLE_ADMIN, null)) {
            throw $this->createAccessDeniedException('Access denied.');
        }

        $requestData = $request->toArray();

        $user = new User();

        if (!array_key_exists('firstName', $requestData) || !$this->userValidator->validateUserFirstName($requestData['firstName']))
        {
            throw new BadRequestHttpException('The first name has not been provided or the provided first name is not valid.');
        }

        if (!array_key_exists('lastName', $requestData) || !$this->userValidator->validateUserLastName($requestData['lastName']))
        {
            throw new BadRequestHttpException('The last name has not been provided or the provided last name is not valid.');
        }

        if (!array_key_exists('email', $requestData) || !$this->userValidator->validateUserEmail($requestData['email']))
        {
            throw new BadRequestHttpException('The e-mail has not been provided or the provided e-mail is not valid.');
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

        return $this->apiResponseInterface->createResponse('', 'success', Response::HTTP_OK);
    }
}