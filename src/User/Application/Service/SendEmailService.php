<?php

namespace App\User\Application\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Serializer\SerializerInterface;

class SendEmailService
{
    private MailerInterface $mailer;
    private SerializerInterface $serializer;

    public function __construct(
        MailerInterface $mailer,
        SerializerInterface $serializer
    ) {
        $this->mailer = $mailer;
        $this->serializer = $serializer;
    }

    public function handle(string $emailReceiver, string $emailTitle, string $emailContent): string
    {
        $emailMessage = new Email();

        $emailMessage->to($emailReceiver);
        $emailMessage->subject($emailTitle);
        $emailMessage->text($emailContent);

        $this->mailer->send($emailMessage);

        return $this->serializer->serialize($emailMessage, 'json');
    }
}