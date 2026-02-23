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
        HubInterface $hub
    ): Response {
        $sender = $this->getUser();

        if ($sender === $receiver) {
            throw $this->createAccessDeniedException("Vous ne pouvez pas discuter avec vous-même.");
        }

        // Récupère l'historique de la conversation
        $messages = $messageRepository->findConversation($sender, $receiver);

        // Envoi du message
        if ($request->isMethod('POST')) {
            $content = trim($request->request->get('content', ''));

            if ($content !== '') {
                $message = new Message();
                $message->setSender($sender);
                $message->setReceiver($receiver);
                $message->setContent($content);

                $em->persist($message);
                $em->flush();

                // Publication Mercure pour les deux participants
                $update = new Update(
                    topics: [
                        "chat/{$sender->getId()}/{$receiver->getId()}",
                        "chat/{$receiver->getId()}/{$sender->getId()}"
                    ],
                    data: json_encode([
                        'id'        => $message->getId(),
                        'content'   => $message->getContent(),
                        'senderId'  => $sender->getId(),
                        'createdAt' => $message->getCreatedAt()->format(\DateTime::ATOM),
                    ]),
                    private: false
                );

                $hub->publish($update);

                // ✅ Réponse AJAX complète
                if ($request->isXmlHttpRequest()) {
                    return $this->json([
                        'id'        => $message->getId(),
                        'content'   => $message->getContent(),
                        'senderId'  => $sender->getId(),
                        'createdAt' => $message->getCreatedAt()->format(\DateTime::ATOM),
                    ]);
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