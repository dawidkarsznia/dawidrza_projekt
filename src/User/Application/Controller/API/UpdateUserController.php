<?php

namespace App\User\Application\Controller\API;

use App\User\Domain\Entity\User;
use App\User\Application\ApiResponse\ApiResponseInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Application\Validation\UserValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class UpdateUserController extends AbstractController
{
    private UserRepositoryInterface $userRepository;
    private ApiResponseInterface $apiResponseInterface;
    private UserValidator $userValidator;

    public function __construct(
        UserRepositoryInterface $userRepository,
        ApiResponseInterface $apiResponseInterface,
        UserValidator $userValidator
    )
    {
        $this->userRepository = $userRepository;
        $this->apiResponseInterface = $apiResponseInterface;
        $this->userValidator = $userValidator;
    }

    public function updateUser(Request $request, int $id): JsonResponse
    {
        // Deny access to this function, if the user is not an administrator.
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);

        $user = $this->userRepository->findOneUserBy(['id' => $id]);
        if (null === $user)
        {
            throw new NotFoundHttpException('The desired resource could not be found.');
        }

        $jsonRequest = $request->toArray();

        $exceptionMessage = '';
        $encounterException = false;
        // Check whether a new first name has actually been given.
        // Then, validate and store the result.
        if (true === array_key_exists('firstName', $jsonRequest))
        {
            if (!$this->userValidator->validateUserFirstName($jsonRequest['firstName']))
            {
                $returnMessage = 'The given first name is invalid.';
                $encounterException = true;
            }
            else
            {
                $user->setFirstName($jsonRequest['firstName']);
            }
        }

        // Check whether a new last name has actually been given.
        // Then, validate and store the result.
        if (true === array_key_exists('lastName', $jsonRequest))
        {
            if (!$this->userValidator->validateUserLastName($jsonRequest['lastName']))
            {
                $returnMessage = 'The given last name is invalid.';
                $encounterException = true;
            }
            else
            {
                $user->setLastName($jsonRequest['lastName']);
            }
        }

        // Check whether a new e-mail has actually been given.
        // Then, validate and store the result.
        if (true === array_key_exists('email', $jsonRequest))
        {
            if (!$this->userValidator->validateUserEmail($jsonRequest['email']))
            {
                $returnMessage = 'The given e-mail is invalid.';
                $encounterException = true;
            }
            else
            {
                $user->setEmail($jsonRequest['email']);
            }
        }

        $this->userRepository->saveUser($user);

        if (true === $encounterException)
        {
            throw new BadRequestHttpException($exceptionMessage);
        }

        return $this->apiResponseInterface->createResponse('', 'success', Response::HTTP_OK);
    }
}