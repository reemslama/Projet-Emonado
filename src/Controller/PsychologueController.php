<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\JournalRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

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
            ->setParameter('role', '%"ROLE_PATIENT"%')
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
