<?php
namespace App\Controller;

use App\Service\Util;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class TestController extends AbstractController
{
    #[Route('/test/token', name: 'test_token', methods: ['GET'])]
    public function testToken(Request $request): JsonResponse
    {
        $token = Util::generateToken();

        return new JsonResponse(['token' => $token]);
    }
    #[Route('/test/pin', name: 'test_pin', methods: ['GET'])]
    public function testPin(Request $request): JsonResponse
    {
        $pin = Util::generatePin();

        return new JsonResponse(['pin' => $pin]);
    }
}
