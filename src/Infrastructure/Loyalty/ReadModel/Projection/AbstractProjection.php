<?php
declare(strict_types=1);

namespace App\Infrastructure\Loyalty\ReadModel\Projection;


use App\Domain\Event;

class AbstractProjection
{
    public function __invoke(Event $event): void
    {
        $className = get_class($event);
        $eventName = substr($className, strrpos($className, '\\') + 1);
        $projectionMethod = sprintf('apply%s', $eventName);

        if (method_exists($this, $projectionMethod)) {
            $this->$projectionMethod($event);
        }
    }
}
