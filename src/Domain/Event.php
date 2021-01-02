<?php

declare(strict_types=1);

namespace App\Domain;

abstract class Event
{
    private string $aggregateId;
    private \DateTimeImmutable $createdAt;
    private int $version = -1;

    public function __construct(string $aggregateId)
    {
        $this->aggregateId = $aggregateId;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getAggregateId(): string
    {
        return $this->aggregateId;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function setVersion(int $version): void
    {
        $this->version = $version;
    }
}
