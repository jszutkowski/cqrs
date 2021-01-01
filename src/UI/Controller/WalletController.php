<?php
declare(strict_types=1);

namespace App\UI\Controller;

use App\Application\Command\AddPoints;
use App\Application\Command\AddPointsHandler;
use App\Application\Command\CreateWallet;
use App\Application\Command\CreateWalletHandler;
use App\Application\Command\WithdrawPoints;
use App\Application\Command\WithdrawPointsHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/wallet")
 */
class WalletController
{
    /**
     * @var CreateWalletHandler
     */
    private CreateWalletHandler $createWalletHandler;
    /**
     * @var AddPointsHandler
     */
    private AddPointsHandler $addPointsHandler;
    /**
     * @var WithdrawPointsHandler
     */
    private WithdrawPointsHandler $withdrawPointsHandler;

    public function __construct(CreateWalletHandler $createWalletHandler,
                                AddPointsHandler $addPointsHandler,
                                WithdrawPointsHandler $withdrawPointsHandler)
    {

        $this->createWalletHandler = $createWalletHandler;
        $this->addPointsHandler = $addPointsHandler;
        $this->withdrawPointsHandler = $withdrawPointsHandler;
    }

    /**
     * @Route("/", name="api_wallet_get", methods={"GET"})
     */
    public function get(): Response
    {

    }

    /**
     * @Route("/", name="api_wallet_post", methods={"POST"})
     */
    public function post(Request $request): Response
    {
        $commandName = $request->headers->get('X-Command-Name');

        switch ($commandName) {
            case 'CreateWallet':
                $this->createWalletHandler->__invoke(new CreateWallet());
                break;
            case 'AddPoints':
                $this->addPointsHandler->__invoke(
                    new AddPoints($request->get('walletId'), $request->request->getInt('points'))
                );
                break;
            case 'WithdrawPoints':
                $this->withdrawPointsHandler->__invoke(
                    new WithdrawPoints($request->get('walletId'), $request->request->getInt('points'))
                );
                break;
            default:
                return new JsonResponse(sprintf('Unsupported command: %s', $commandName), Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(null, Response::HTTP_ACCEPTED);
    }
}
