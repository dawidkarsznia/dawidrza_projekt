<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Response;

use App\ResponseFormat\ResponseFormatInterface;

final class ExceptionListener
{
    public function __construct(private ResponseFormatInterface $responseFormat)
    {

    }

    #[AsEventListener(event: KernelEvents::EXCEPTION)]
    public function __invoke(ExceptionEvent $event): void
    {
        // You get the exception object from the received event
        $exception = $event->getThrowable();

        $responseMessage = sprintf('%s', $exception->getMessage());
        $responseData = json_encode(['message' => $responseMessage]);
        $responseCode = $exception->getStatusCode() ?? Response::HTTP_BAD_REQUEST;

        // Customize your response object to display the exception details
        $response = $this->responseFormat->createFail($responseData, $responseCode);

        // sends the modified response object to the event
        $event->setResponse($response);
    }
}
