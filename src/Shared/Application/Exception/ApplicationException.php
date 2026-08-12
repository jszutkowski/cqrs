<?php

declare(strict_types=1);

namespace App\Shared\Application\Exception;

/**
 * Base class for exceptions crossing the application boundary. Domain
 * exceptions never leave the application layer — handlers translate them into
 * subclasses of this type, and the UserInterface layer maps those to HTTP.
 */
abstract class ApplicationException extends \RuntimeException
{
    final protected function __construct(string $message)
    {
        parent::__construct($message);
    }
}
