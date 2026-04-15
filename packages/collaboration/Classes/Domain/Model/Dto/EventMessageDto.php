<?php

declare(strict_types=1);

namespace TYPO3Incubator\Collaboration\Domain\Model\Dto;

class EventMessageDto
{
    private int $timestamp;
    private int $ownerId;
    private string $ownerName;
    private string $usersToInform;
    private string $name;
    private string $message;

    public function __construct(int $timestamp, int $ownerId, string $ownerName, string $name, string $message, string $usersToInform = '')
    {
        $this->timestamp = $timestamp;
        $this->ownerId = $ownerId;
        $this->ownerName = $ownerName;
        $this->message = $message;
        $this->name = $name;
        $this->usersToInform = $usersToInform;
    }

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    public function getOwnerId(): int
    {
        return $this->ownerId;
    }

    public function getOwnerName(): string
    {
        return $this->ownerName;
    }

    public function getUsersToInform(): string
    {
        return $this->usersToInform;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function toArray(): array
    {
        return [
            'timestamp' => $this->getTimestamp(),
            'owner_id' => $this->getOwnerId(),
            'owner_name' => $this->getOwnerName(),
            'users_to_inform' => $this->getUsersToInform(),
            'name' => $this->getName(),
            'message' => $this->getMessage(),
        ];
    }
}
