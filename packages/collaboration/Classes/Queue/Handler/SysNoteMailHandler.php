<?php

declare(strict_types=1);

namespace TYPO3Incubator\Collaboration\Queue\Handler;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Routing\RequestContext;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Mail\MailerInterface;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3Incubator\Collaboration\Queue\Message\SysNoteMailMessage;

#[AsMessageHandler]
class SysNoteMailHandler
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly UriBuilder $backendUriBuilder,
    ) {}

    public function __invoke(SysNoteMailMessage $message)
    {
        // ToDo: Get scheme and host from site
        $this->backendUriBuilder->setRequestContext(new RequestContext(
            baseUrl: '/typo3/',
            host: 'surfcamp-team2.ddev.site',
            scheme: 'https',
        ));
        $backendUrl = $this->backendUriBuilder
            ->buildUriFromRoute('web_layout', ['id' => $message->pid], UriBuilder::SHAREABLE_URL);
        $localization = GeneralUtility::makeInstance(LocalizationUtility::class);
        $mail = GeneralUtility::makeInstance(MailMessage::class);
        $mail->to($message->email)
            ->from('team2@surfcamp.de')
            ->subject($localization->translate('sys_note.mail.subject', 'collaboration'))
            ->html(
                $localization->translate(
                    'sys_note.mail.message',
                    'collaboration',
                    [$message->title]
                )
                . '<br />' .
                '<a href="' . $backendUrl . '" target="blank">' . $backendUrl . '</a>'
            );
        $this->mailer->send($mail);
    }
}
