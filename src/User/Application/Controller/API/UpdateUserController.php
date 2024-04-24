<?php

namespace App\User\Application\Controller\API;

use App\User\Domain\Entity\User;
use App\User\Application\ApiResponse\ApiResponseInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

final class UpdateUserController extends AbstractController
{
    private UserRepositoryInterface $userRepository;
    private ApiResponseInterface $apiResponseInterface;

    public function __construct(
        UserRepositoryInterface $userRepository,
        ApiResponseInterface $apiResponseInterface
    )
    {
        $this->userRepository = $userRepository;
        $this->apiResponseInterface = $apiResponseInterface;
    }

    public function updateUser(Request $request): JsonResponse
    {
        // Deny access to this function, if the user is not an administrator.
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);

        $user = $this->userRepository->findOneBy(['id' => $id]);
        if (null === $user)
        {
            throw new NotFoundHttpException('The desired resource could not be found.');
        }

        $jsonRequest = $request->toArray();

        // Check whether a new first name has actually been given.
        // Then, validate and store the result.
        if (true === array_key_exists('firstName', $jsonRequest))
        {
            if (!$this->userValidator->validateUserFirstName($jsonRequest['firstName']))
            {
                throw new BadRequestHttpException('The given first name is invalid.');
            }

            $user->setFirstName($jsonRequest['firstName']);
        }

        // Check whether a new last name has actually been given.
        // Then, validate and store the result.
        if (true === array_key_exists('lastName', $jsonRequest))
        {
            if (!$this->userValidator->validateUserLastName($jsonRequest['lastName']))
            {
                throw new BadRequestHttpException('The given last name is invalid.');
            }

            $user->setLastName($jsonRequest['lastName']);
        }

        // Check whether a new e-mail has actually been given.
        // Then, validate and store the result.
        if (true === array_key_exists('email', $jsonRequest))
        {
            if (!$this->userValidator->validateUserEmail($jsonRequest['email']))
            {
                throw new BadRequestHttpException('The given e-mail is invalid.');
            }

            $user->setEmail($jsonRequest['lastName']);
        }

        $this->entityManager->saveUser($user);

        return $this->apiResponseInterface->createResponse($jsonData, 'success', Response::HTTP_OK);
    }

    public function resetUserApiKey(Request $request): JsonResponse
    {
        $apiKey = $request->headers->get('Authorization');
        $user = $this->userRepository->findOneBy(['apiKey' => $apiKey]);
        if (null === $user)
        {
            throw new NotFoundHttpException('The desired resource could not be found.');
        }

        $generatedApiKey = $this->generateApiKeyService->handle();
        $user->setApiKey($generatedApiKey);

        $this->userRepository->saveUser($user);

        $jsonData = json_encode([
            'oldApiKey' => $apiKey,
            'newApiKey' => $generatedApiKey
        ]);

        return $this->apiResponseInterface->createResponse($jsonData, 'success', Response::HTTP_OK);
    }

    public function resetUserPassword(Request $request): JsonResponse
    {
        $apiKey = $request->headers->get('Authorization');
        $user = $this->userRepository->findOneBy(['apiKey' => $apiKey]);
        if (null === $user)
        {
            throw new NotFoundHttpException('The desired resource could not be found.');
        }

        $generatedPassword = $this->generatePasswordService->handle();

        // TODO: Send the newly generated password via e-mail.
        /* $this->sendEmailService->handle(
            $input->getArgument('email'),
            'Newly created password to be changed.',
            sprintf('%s', $plainPassword)
        ); */

        return JsonResponse::fromJsonString('');
    }

    public function blockUser(Request $request, int $id): JsonResponse
    {
        // Deny access to this function, if the user is not an administrator.
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);

        // Find the user by the 'id' provided.
        $user = $this->userRepository->findOneBy(['id' => $id]);
        if (null === $user)
        {
            throw new NotFoundHttpException('The desired resource could not be found.');
        }

        $user->setActive(false);

        $this->userRepository->saveUser($user);

        return $this->apiResponseInterface->createResponse('', 'success', Response::HTTP_OK);
    }

    public function unblockUser(Request $request, int $id): JsonResponse
    {
        // Deny access to this function, if the user is not an administrator.
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);

        // Find the user by the 'id' provided.
        $user = $this->userRepository->findOneBy(['id' => $id]);
        if (null === $user)
        {
            throw new NotFoundHttpException('The desired resource could not be found.');
        }

        $user->setActive(false);

        $this->userRepository->saveUser($user);

        return $this->apiResponseInterface->createResponse('', 'success', Response::HTTP_OK);
    }
}