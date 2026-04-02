<?php

namespace App\Controller;

use App\Entity\Message;
use App\Entity\User;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class MessageController extends AbstractController
{
    #[Route('/chat/{id}', name: 'chat', methods: ['GET', 'POST'])]
    public function chat(
        User $receiver,
        MessageRepository $messageRepository,
        Request $request,
        EntityManagerInterface $em,
        HubInterface $hub,
        LoggerInterface $logger
    ): Response {
        $sender = $this->getUser();
        if (!$sender instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if ($sender === $receiver) {
            throw $this->createAccessDeniedException("Vous ne pouvez pas discuter avec vous-même.");
        }

        // Récupère l'historique de la conversation
        $messages = $messageRepository->findConversation($sender, $receiver);

        // Envoi du message
        if ($request->isMethod('POST')) {
            $content = trim((string) $request->request->get('content', ''));

            if ($content !== '') {
                $message = new Message();
                $message->setSender($sender);
                $message->setReceiver($receiver);
                $message->setContent($content);

                $em->persist($message);
                $em->flush();

                // Publication Mercure pour les deux participants
                $createdAt = $message->getCreatedAt();
                $payload = [
                    'id'        => $message->getId(),
                    'content'   => $message->getContent(),
                    'senderId'  => $sender->getId(),
                    'createdAt' => $createdAt ? $createdAt->format(\DateTime::ATOM) : null,
                ];
                $update = new Update(
                    topics: [
                        "chat/{$sender->getId()}/{$receiver->getId()}",
                        "chat/{$receiver->getId()}/{$sender->getId()}"
                    ],
                    data: json_encode($payload, JSON_THROW_ON_ERROR),
                    private: false
                );

                try {
                    $hub->publish($update);
                } catch (\Throwable $e) {
                    // In dev the Mercure hub often uses a self-signed certificate; don't fail the whole request.
                    $logger->error('Echec d\'envoi Mercure', ['exception' => $e]);
                    $this->addFlash('warning', 'Message enregistre, mais la notification temps reel est temporairement indisponible.');
                }

                // ✅ Réponse AJAX complète
                if ($request->isXmlHttpRequest()) {
                    return $this->json($payload);
                }

                $this->addFlash('success', 'Message envoyé !');
                return $this->redirectToRoute('chat', ['id' => $receiver->getId()]);
            }
        }

        return $this->render('chat/index.html.twig', [
            'messages' => $messages,
            'receiver' => $receiver,
        ]);
    }
}
