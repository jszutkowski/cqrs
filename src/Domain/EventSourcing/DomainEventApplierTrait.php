<?php

declare(strict_types=1);

namespace App\Domain\EventSourcing;

trait DomainEventApplierTrait
{
    public function apply(DomainEventsStream $events): void
    {
        foreach ($events as $event) {
            $className = get_class($event);
            $eventName = substr($className, strrpos($className, '\\') + 1);
            $methodName = sprintf('apply%s', $eventName);

            if (method_exists($this, $methodName)) {
                $this->$methodName($event);
            }
        }
    }
}
