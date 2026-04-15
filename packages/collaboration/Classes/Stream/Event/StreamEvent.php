<?php

declare(strict_types=1);

namespace TYPO3Incubator\Collaboration\Stream\Event;

use EliasHaeussler\SSE\Event\Event;

class StreamEvent implements Event
{
    private string $eventName;
    private array $eventData;

    public function __construct(string $eventName, array $eventData)
    {
        $this->eventData = $eventData;
        $this->eventName = $eventName;

    }
    public function getName(): string
    {
        return $this->eventName;
    }

    public function getData(): array
    {
        return $this->eventData;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'eventName' => $this->eventName,
            'eventData' => $this->eventData,
        ];
    }
}
