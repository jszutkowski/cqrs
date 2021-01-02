<?php

declare(strict_types=1);

namespace App\UI\Controller;

use App\Application\Command\AddPoints;
use App\Application\Command\CreateWallet;
use App\Application\System\CommandBusInterface;
use App\Infrastructure\ReadModel\Query\MySqlWallets;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/wallet")
 * @SWG\Tag(name="Wallet")
 */
class WalletController
{
    private CommandBusInterface $commandBus;

    public function __construct(CommandBusInterface $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    /**
     * Get all wallets.
     *
     * @Route("", name="api_wallet_all_get", methods={"GET"})
     *
     * @SWG\Response(
     *     response=200,
     *     description="Returns wallets with current points balance each",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=App\Application\ReadModel\View\SimpleWallet::class))
     *     )
     * )
     *
     * @SWG\Response(response=401, description="Access denied")
     *
     * @Security(name="Bearer")
     */
    public function getWallets(MySqlWallets $wallets, Request $request): Response
    {
        return new JsonResponse($wallets->getWallets(), Response::HTTP_OK);
    }

    /**
     * Get single wallet.
     *
     * @Route("/{walletId}", name="api_wallet_get_wallet", methods={"GET"})
     *
     * @SWG\Response(
     *     response=200,
     *     description="Returns wallets with points history and balance",
     *     @Model(type=App\Application\ReadModel\View\FullWalletView\Wallet::class)
     * )
     *
     * @SWG\Response(
     *     response=404,
     *     description="Wallet not found",
     *     @SWG\Schema(
     *        type="object",
     *        example={"message": "string"}
     *     )
     * )
     *
     * @SWG\Response(response=401, description="Access denied")
     *
     * @Security(name="Bearer")
     */
    public function getWallet(MySqlWallets $wallets, Request $request): Response
    {
        $wallet = $wallets->getWallet($request->attributes->get('walletId'));

        if (null === $wallet) {
            return new JsonResponse(['message' => 'Wallet not found'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($wallet, Response::HTTP_OK);
    }

    /**
     * Create wallet.
     *
     * @Route("", name="api_wallet_create_wallet", methods={"POST"})
     *
     * @SWG\Response(
     *     response=202,
     *     description="Wallet created"
     * )
     *
     * @SWG\Response(response=401, description="Access denied")
     *
     * @Security(name="Bearer")
     */
    public function create(): Response
    {
        $this->commandBus->dispatch(new CreateWallet());

        return new JsonResponse(null, Response::HTTP_ACCEPTED);
    }

    /**
     * Add points to wallet.
     *
     * @Route("/{walletId}",
     *     name="api_wallet_add_points",
     *     methods={"POST"},
     *     condition="request.headers.get('X-Command-Name') === 'AddPoints'")
     *
     * @SWG\Response(
     *     response=202,
     *     description="Points added",
     *     @Model(type=App\UI\Request\AddPoints::class)
     * )
     *
     * @SWG\Response(response=401, description="Access denied")
     *
     * @Security(name="Bearer")
     */
    public function addPoints(string $walletId, Request $request): Response
    {
        $data = $request->toArray();

        $this->commandBus->dispatch(new AddPoints($walletId, $data['points']));

        return new JsonResponse(null, Response::HTTP_ACCEPTED);
    }
}
