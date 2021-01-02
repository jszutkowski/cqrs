<?php

declare(strict_types=1);

namespace App\UI\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api")
 */
class SecurityController
{
    /**
     * @Route("/auth/login", name="app_login", methods={"POST"})
     */
    public function login(): Response
    {
        throw new \Exception('Don\'t forget to activate login in security.yaml');
    }
}
