<?php

declare(strict_types=1);

namespace App\Shared\UserInterface\Api\Error;

use App\Loyalty\Application\Exception\TransferDoesNotExist;
use App\Loyalty\Application\Exception\TransferRejected;
use App\Loyalty\Application\Exception\WalletDoesNotExist;
use App\Shared\Application\Exception\ApplicationException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

/**
 * Single place translating application exceptions into HTTP status codes.
 * Domain exceptions never reach here — handlers convert them first — so this
 * listener stays a thin, complete mapping instead of a growing catch-all.
 */
#[AsEventListener(event: ExceptionEvent::class)]
final readonly class ExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if (!$exception instanceof ApplicationException) {
            return;
        }

        $statusCode = match ($exception::class) {
            WalletDoesNotExist::class, TransferDoesNotExist::class => Response::HTTP_NOT_FOUND,
            TransferRejected::class => Response::HTTP_UNPROCESSABLE_ENTITY,
            default => Response::HTTP_INTERNAL_SERVER_ERROR,
        };

        $event->setResponse(new JsonResponse(['message' => $exception->getMessage()], $statusCode));
    }
}
