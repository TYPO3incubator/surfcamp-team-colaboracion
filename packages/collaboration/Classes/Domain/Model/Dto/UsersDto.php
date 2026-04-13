<?php

declare(strict_types=1);

namespace TYPO3Incubator\Collaboration\Domain\Model\Dto;

final class UsersDto
{
    private int $userid;
    private string $username;

    public function __construct(int $userid, string $username)
    {
        $this->userid = $userid;
        $this->username = $username;
    }

    public function getUserid(): int
    {
        return $this->userid;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function __toArray(): array
    {
        return [
            'userid' => $this->getUserid(),
            'username' => $this->getUsername(),
        ];
    }
}
