<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\JournalRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class PsychologueController extends AbstractController
{
    private function assertPsyArea(): void
    {
        $this->denyAccessUnlessGranted('ROLE_PSYCHOLOGUE');
    }

    #[Route('/psychologue', name: 'psychologue_index')]
    public function index(EntityManagerInterface $em, JournalRepository $journalRepository): Response
    {
        $this->assertPsyArea();

        $psy = $this->getUser();

        // Tous les patients de l'application
        $patients = $em->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_PATIENT%')
            ->orderBy('u.nom', 'ASC')
            ->addOrderBy('u.prenom', 'ASC')
            ->getQuery()
            ->getResult();

        $pendingVoiceCount = $journalRepository->countPendingVoiceCases();

        return $this->render('psychologue/index.html.twig', [
            'patients' => $patients,
            'pendingVoiceCount' => $pendingVoiceCount,
        ]);
    }

    #[Route('/psychologue/journaux', name: 'psychologue_journals', methods: ['GET'])]
    public function journals(Request $request, JournalRepository $journalRepository): Response
    {
        $this->assertPsyArea();

        $keyword = (string) $request->query->get('q', '');
        $sort = (string) $request->query->get('sort', 'recent');

        return $this->render('psychologue/journals.html.twig', [
            'journals' => $journalRepository->searchAndSortAll($keyword, $sort),
            'keyword' => $keyword,
            'sort' => $sort,
        ]);
    }

    #[Route('/psychologue/vocaux', name: 'psychologue_voice_cases', methods: ['GET'])]
    public function voiceCases(JournalRepository $journalRepository): Response
    {
        $this->assertPsyArea();

        $now = new \DateTimeImmutable();
        $rows = [];
        foreach ($journalRepository->findPendingVoiceCases() as $case) {
            $createdAt = $case->getDateCreation() ?? $now;
            $minutes = max(0, (int) floor(($now->getTimestamp() - $createdAt->getTimestamp()) / 60));
            $rows[] = [
                'case' => $case,
                'minutes' => $minutes,
                'priority' => $minutes >= 120 ? 'haute' : ($minutes >= 30 ? 'moyenne' : 'normale'),
            ];
        }

        return $this->render('psychologue/voice_cases.html.twig', [
            'rows' => $rows,
        ]);
    }

    #[Route('/psychologue/vocaux/{id}/conseil', name: 'psychologue_voice_case_advise', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function adviseVoiceCase(
        Request $request,
        int $id,
        JournalRepository $journalRepository,
        EntityManagerInterface $em
    ): Response {
        $this->assertPsyArea();

        $journal = $journalRepository->find($id);
        if (!$journal || $journal->getInputMode() !== 'voice') {
            $this->addFlash('error', 'Journal vocal introuvable.');
            return $this->redirectToRoute('psychologue_voice_cases');
        }

        if (!$this->isCsrfTokenValid('advise_voice_case_' . $journal->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('psychologue_voice_cases');
        }

        $advice = trim((string) $request->request->get('patient_advice', ''));
        if (mb_strlen($advice) < 15) {
            $this->addFlash('error', 'Le conseil doit contenir au moins 15 caracteres.');
            return $this->redirectToRoute('psychologue_voice_cases');
        }

        $journal->setPsychologueCaseDescription($advice);
        $journal->markPsychologueReviewedAt(new \DateTimeImmutable());
        $em->flush();

        $this->addFlash('success', 'Conseil envoye au patient.');
        return $this->redirectToRoute('psychologue_voice_cases');
    }

    #[Route('/psychologue/profil', name: 'psychologue_profil')]
    public function profil(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $this->assertPsyArea();

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isMethod('POST')) {
            $user->setNom((string) $request->request->get('nom'));
            $user->setPrenom((string) $request->request->get('prenom'));
            $tel = $request->request->get('telephone');
            $user->setTelephone(is_string($tel) && $tel !== '' ? $tel : null);
            $spec = $request->request->get('specialite');
            $user->setSpecialite(is_string($spec) && $spec !== '' ? $spec : null);

            $password = $request->request->get('password');
            if (is_string($password) && $password !== '') {
                $user->setPassword($passwordHasher->hashPassword($user, $password));
            }

            $em->flush();
            $this->addFlash('success', 'Profil mis à jour avec succès !');
            return $this->redirectToRoute('psychologue_profil');
        }

        return $this->render('profil_psychologue/index.html.twig', [
            'user' => $user,
            'error' => null,
        ]);
    }

    #[Route('/psychologue/profil/delete', name: 'psychologue_profil_delete', methods: ['POST'])]
    public function delete(EntityManagerInterface $em): Response
    {
        $this->assertPsyArea();

        $user = $this->getUser();
        if ($user) {
            $em->remove($user);
            $em->flush();
            $this->addFlash('success', 'Profil supprimé avec succès !');
        }

        return $this->redirectToRoute('app_login');
    }
}
