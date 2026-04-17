<?php

declare(strict_types=1);

namespace TYPO3Incubator\Collaboration\Queue\Message;

class SysNoteMailMessage
{
    public function __construct(
        public readonly string $email
    ) {}

    public function getEmail(): string
    {
        return $this->email;
    }
}
