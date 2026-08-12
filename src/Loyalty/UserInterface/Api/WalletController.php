<?php

declare(strict_types=1);

namespace App\Loyalty\UserInterface\Api;

use App\Loyalty\Application\Command\AddPoints;
use App\Loyalty\Application\Command\CreateWallet;
use App\Loyalty\Application\Query\GetWalletQuery;
use App\Loyalty\Application\Query\GetWalletQueryHandler;
use App\Loyalty\Application\Query\ListWalletsQuery;
use App\Loyalty\Application\Query\ListWalletsQueryHandler;
use App\Loyalty\Application\ReadModel\WalletDetails;
use App\Loyalty\Application\ReadModel\WalletSummary;
use App\Loyalty\UserInterface\Api\Request\AddPointsRequest;
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
#[Route('/api/wallets')]
#[OA\Tag(name: 'Wallets')]
final readonly class WalletController
{
    public function __construct(
        private CommandBus $commandBus,
    ) {
    }

    /**
     * Writes go through the asynchronous command bus, so they answer 202 with
     * the identifier the caller should poll — the read model catches up shortly
     * after, and the client is told about it over the websocket in the meantime.
     */
    #[Route('', name: 'api_wallets_create', methods: ['POST'])]
    #[OA\Response(response: 202, description: 'Wallet creation accepted')]
    public function create(): Response
    {
        $walletId = Uuid::v4()->toRfc4122();

        $this->commandBus->dispatch(new CreateWallet($walletId));

        return new JsonResponse(['walletId' => $walletId], Response::HTTP_ACCEPTED);
    }

    #[Route('', name: 'api_wallets_list', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'All wallets with their current balance',
        content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: WalletSummary::class))),
    )]
    public function list(ListWalletsQueryHandler $listWallets): Response
    {
        return new JsonResponse($listWallets(new ListWalletsQuery()), Response::HTTP_OK);
    }

    #[Route('/{walletId}', name: 'api_wallets_get', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Wallet with its points history',
        content: new Model(type: WalletDetails::class),
    )]
    #[OA\Response(response: 404, description: 'Wallet not found')]
    public function get(string $walletId, GetWalletQueryHandler $getWallet): Response
    {
        return new JsonResponse($getWallet(new GetWalletQuery($walletId)), Response::HTTP_OK);
    }

    #[Route('/{walletId}/points', name: 'api_wallets_add_points', methods: ['POST'])]
    #[OA\RequestBody(content: new Model(type: AddPointsRequest::class))]
    #[OA\Response(response: 202, description: 'Points accepted')]
    #[OA\Response(response: 422, description: 'Invalid payload')]
    public function addPoints(
        string $walletId,
        #[MapRequestPayload] AddPointsRequest $request,
    ): Response {
        \assert(null !== $request->points);

        $this->commandBus->dispatch(new AddPoints($walletId, $request->points));

        return new JsonResponse(null, Response::HTTP_ACCEPTED);
    }
}
