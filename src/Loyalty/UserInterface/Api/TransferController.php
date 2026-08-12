<?php

declare(strict_types=1);

namespace App\Loyalty\UserInterface\Api;

use App\Loyalty\Application\Command\InitiateTransfer;
use App\Loyalty\Application\Query\GetTransferQuery;
use App\Loyalty\Application\Query\GetTransferQueryHandler;
use App\Loyalty\Application\ReadModel\TransferSummary;
use App\Loyalty\UserInterface\Api\Request\InitiateTransferRequest;
use App\Shared\Application\Command\CommandBus;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[AsController]
#[Route('/api/transfers')]
#[OA\Tag(name: 'Transfers')]
final readonly class TransferController
{
    public function __construct(
        private CommandBus $commandBus,
    ) {
    }

    /**
     * Starts the transfer saga. The response only says the request was accepted:
     * whether the points actually moved is decided asynchronously by the
     * transfer process manager, and is readable from GET /api/transfers/{id}.
     */
    #[Route('', name: 'api_transfers_initiate', methods: ['POST'])]
    #[OA\RequestBody(content: new Model(type: InitiateTransferRequest::class))]
    #[OA\Response(response: 202, description: 'Transfer accepted for processing')]
    #[OA\Response(response: 422, description: 'Invalid payload')]
    public function initiate(#[MapRequestPayload] InitiateTransferRequest $request): Response
    {
        \assert(null !== $request->sourceWalletId && null !== $request->targetWalletId && null !== $request->points);

        $transferId = Uuid::v4()->toRfc4122();

        $this->commandBus->dispatch(new InitiateTransfer(
            $transferId,
            $request->sourceWalletId,
            $request->targetWalletId,
            $request->points,
        ));

        return new JsonResponse(['transferId' => $transferId], Response::HTTP_ACCEPTED);
    }

    #[Route('/{transferId}', name: 'api_transfers_get', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Current state of the transfer',
        content: new Model(type: TransferSummary::class),
    )]
    #[OA\Response(response: 404, description: 'Transfer not found')]
    public function get(string $transferId, GetTransferQueryHandler $getTransfer): Response
    {
        return new JsonResponse($getTransfer(new GetTransferQuery($transferId)), Response::HTTP_OK);
    }
}
