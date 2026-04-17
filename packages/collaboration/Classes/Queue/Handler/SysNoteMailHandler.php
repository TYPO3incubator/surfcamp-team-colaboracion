<?php

declare(strict_types=1);

namespace TYPO3Incubator\Collaboration\Queue\Handler;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use TYPO3\CMS\Core\Mail\MailerInterface;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Incubator\Collaboration\Queue\Message\SysNoteMailMessage;

#[AsMessageHandler]
class SysNoteMailHandler
{
    public function __construct(
        private readonly MailerInterface $mailer
    ) {}

    public function __invoke(SysNoteMailMessage $message)
    {
        $mail = GeneralUtility::makeInstance(MailMessage::class);
        $mail->to($message->getEmail())
            ->from('team2@surfcamp.de')
            ->subject('A Backend Note was assigned to you')
            ->html('test');
        $this->mailer->send($mail);
    }
}
