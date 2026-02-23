<?php
namespace App\Controller;

use App\Service\GeminiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class ChatBotController extends AbstractController
{
    #[Route('/patient/chatbot', name: 'patient_chatbot', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_PATIENT');
        return $this->render('patient/chatbot.html.twig');
    }

    #[Route('/patient/chatbot/ask', name: 'patient_chatbot_ask', methods: ['POST'])]
    #[IsGranted('ROLE_PATIENT')]
    public function ask(Request $request, GeminiService $gemini, CsrfTokenManagerInterface $csrfTokenManager): JsonResponse
    {
        $token = $request->request->get('_token', '');
        if (!$csrfTokenManager->isTokenValid(new \Symfony\Component\Security\Csrf\CsrfToken('_csrf', $token))) {
            return $this->json(['ok' => false, 'error' => 'Token CSRF invalide.'], Response::HTTP_FORBIDDEN);
        }

        $message = trim((string) $request->request->get('message', ''));
        if ($message === '') {
            return $this->json(['ok' => false, 'error' => 'Le message est vide.'], Response::HTTP_BAD_REQUEST);
        }

        $result = $gemini->ask($message);
        $ok = (bool) ($result['ok'] ?? false);
        $defaultStatus = $ok ? Response::HTTP_OK : Response::HTTP_BAD_GATEWAY;
        $status = (int) ($result['status'] ?? $defaultStatus);

        if ($ok && ($status < 200 || $status > 299)) {
            $status = Response::HTTP_OK;
        } elseif (!$ok && ($status < 400 || $status > 599)) {
            $status = Response::HTTP_BAD_GATEWAY;
        }

        return $this->json([
            'ok' => $ok,
            'reply' => $result['reply'] ?? '',
            'error' => $result['error'] ?? null,
        ], $status);
    }
}
