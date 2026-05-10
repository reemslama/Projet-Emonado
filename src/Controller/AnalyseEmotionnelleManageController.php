<?php

namespace App\Controller;

use App\Entity\AnalyseEmotionnelle;
use App\Entity\Journal;
use App\Entity\User;
use App\Form\AnalyseEmotionnelleEditType;
use App\Repository\AnalyseEmotionnelleRepository;
use App\Repository\JournalRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/analyse/emotionnelle/manage')]
final class AnalyseEmotionnelleManageController extends AbstractController
{
    #[Route('/', name: 'app_analyse_emotionnelle_manage_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_PSYCHOLOGUE');

        $patients = $userRepository->findByRole('ROLE_USER'); // Assuming patients are ROLE_USER

        return $this->render('analyse_emotionnelle_manage/index.html.twig', [
            'patients' => $patients,
        ]);
    }

    #[Route('/patient/{patientId}', name: 'app_analyse_emotionnelle_manage_patient', methods: ['GET'])]
    public function managePatient(
        int $patientId,
        UserRepository $userRepository,
        JournalRepository $journalRepository,
        AnalyseEmotionnelleRepository $analyseEmotionnelleRepository
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_PSYCHOLOGUE');

        $patient = $userRepository->find($patientId);
        if (!$patient) {
            throw $this->createNotFoundException('Patient not found');
        }

        $journals = $journalRepository->searchAndSortByUser($patient, '', 'recent');

        $analyses = [];
        foreach ($journals as $journal) {
            $analyse = $analyseEmotionnelleRepository->findOneByJournalId($journal->getId());
            $analyses[$journal->getId()] = $analyse;
        }

        return $this->render('analyse_emotionnelle_manage/patient.html.twig', [
            'patient' => $patient,
            'journals' => $journals,
            'analyses' => $analyses,
        ]);
    }

    #[Route('/add/{journalId}', name: 'app_analyse_emotionnelle_manage_add', methods: ['GET', 'POST'])]
    public function add(
        int $journalId,
        Request $request,
        JournalRepository $journalRepository,
        AnalyseEmotionnelleRepository $analyseEmotionnelleRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_PSYCHOLOGUE');

        $journal = $journalRepository->find($journalId);
        if (!$journal) {
            throw $this->createNotFoundException('Journal not found');
        }

        $analyse = $analyseEmotionnelleRepository->findOneByJournalId($journalId);
        if ($analyse) {
            return $this->redirectToRoute('app_analyse_emotionnelle_manage_edit', ['analyseId' => $analyse->getId()]);
        }

        $analyse = new AnalyseEmotionnelle();
        $analyse->setJournal($journal);

        $form = $this->createForm(AnalyseEmotionnelleEditType::class, $analyse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $analyse->markAnalyzedAt();
            $entityManager->persist($analyse);
            $entityManager->flush();

            return $this->redirectToRoute('app_analyse_emotionnelle_manage_patient', ['patientId' => $journal->getUser()->getId()]);
        }

        return $this->render('analyse_emotionnelle_manage/edit.html.twig', [
            'form' => $form,
            'journal' => $journal,
            'isNew' => true,
        ]);
    }

    #[Route('/edit/{analyseId}', name: 'app_analyse_emotionnelle_manage_edit', methods: ['GET', 'POST'])]
    public function edit(
        int $analyseId,
        Request $request,
        AnalyseEmotionnelleRepository $analyseEmotionnelleRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_PSYCHOLOGUE');

        $analyse = $analyseEmotionnelleRepository->find($analyseId);
        if (!$analyse) {
            throw $this->createNotFoundException('Analyse not found');
        }

        $form = $this->createForm(AnalyseEmotionnelleEditType::class, $analyse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_analyse_emotionnelle_manage_patient', ['patientId' => $analyse->getJournal()->getUser()->getId()]);
        }

        return $this->render('analyse_emotionnelle_manage/edit.html.twig', [
            'form' => $form,
            'journal' => $analyse->getJournal(),
            'isNew' => false,
        ]);
    }

    #[Route('/delete/{analyseId}', name: 'app_analyse_emotionnelle_manage_delete', methods: ['POST'])]
    public function delete(
        int $analyseId,
        Request $request,
        AnalyseEmotionnelleRepository $analyseEmotionnelleRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_PSYCHOLOGUE');

        $analyse = $analyseEmotionnelleRepository->find($analyseId);
        if (!$analyse) {
            throw $this->createNotFoundException('Analyse not found');
        }

        if ($this->isCsrfTokenValid('delete' . $analyseId, $request->request->get('_token'))) {
            $patientId = $analyse->getJournal()->getUser()->getId();
            $resume = $analyse->getResumeIA();

            // Conseil et déclencheur professionnels sont stockés dans resumeIA au format déclencheur\n---\nconseil.
            // Ne pas effacer un résumé IA seul (sans ce séparateur) ni supprimer la ligne d'analyse : le journal patient reste intact.
            if ($resume !== null && str_contains($resume, "\n---\n")) {
                $analyse->setResumeIA(null);
                $entityManager->flush();
                $this->addFlash('success', 'Le déclencheur et le conseil ont été retirés. Le journal du patient est conservé.');
            } elseif ($resume !== null) {
                $this->addFlash('warning', 'Seuls le conseil et le déclencheur saisis dans le formulaire professionnel peuvent être retirés. Le résumé d\'analyse automatique n\'a pas été modifié.');
            } else {
                $this->addFlash('info', 'Aucun conseil ni déclencheur professionnel à retirer.');
            }

            return $this->redirectToRoute('app_analyse_emotionnelle_manage_patient', ['patientId' => $patientId]);
        }

        throw $this->createAccessDeniedException('Invalid CSRF token');
    }
}