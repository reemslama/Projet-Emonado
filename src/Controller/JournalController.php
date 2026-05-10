<?php

namespace App\Controller;

use App\Entity\Journal;
use App\Entity\User;
use App\Form\JournalType;
use App\Form\VoiceJournalType;
use App\Repository\JournalRepository;
use App\Service\MusicTherapyService;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/journal')]
class JournalController extends AbstractController
{
    private function assertPatientArea(): ?Response
    {
        if (
            $this->isGranted('ROLE_ADMIN')
            || $this->isGranted('ROLE_PSYCHOLOGUE')
            || $this->isGranted('ROLE_PSY')
        ) {
            return $this->redirectToRoute('psychologue_journals');
        }
        return null;
    }

    #[Route('/', name: 'app_journal_index', methods: ['GET'])]
    public function index(Request $request, JournalRepository $journalRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        if ($redirect = $this->assertPatientArea()) {
            return $redirect;
        }

        $keyword = (string) $request->query->get('q', '');
        $sort = (string) $request->query->get('sort', 'recent');
        $openAdviceId = $request->query->getInt('open_advice', 0);
        $openMusicId = $request->query->getInt('open_music', 0);

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $isPsychologue = $this->isGranted('ROLE_PSYCHOLOGUE') || $this->isGranted('ROLE_PSY');

        if ($isPsychologue) {
            $journals = $journalRepository->searchAndSortAll($keyword, $sort);
            $stats = $journalRepository->countByHumeurAll();
        } else {
            $journals = $journalRepository->searchAndSortByUser($user, $keyword, $sort);
            $stats = $journalRepository->countByHumeurForUser($user);
        }

        $analysedCount = 0;
        foreach ($journals as $journal) {
            if ($journal->getAnalysisEmotionnelle() !== null) {
                $analysedCount++;
            }
        }

        return $this->render('journal/index.html.twig', [
            'journals' => $journals,
            'stats' => $stats,
            'analysedCount' => $analysedCount,
            'keyword' => $keyword,
            'sort' => $sort,
            'openAdviceId' => $openAdviceId,
            'openMusicId' => $openMusicId,
        ]);
    }

    #[Route('/{id}/advice-seen', name: 'app_journal_advice_seen', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function markAdviceSeen(
        Request $request,
        int $id,
        JournalRepository $journalRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');
        if ($redirect = $this->assertPatientArea()) {
            return $redirect;
        }

        $journal = $journalRepository->find($id);
        if (!$journal) {
            $this->addFlash('error', 'Journal introuvable.');
            return $this->redirectToRoute('app_journal_index');
        }

        $currentUser = $this->getUser();
        if (!$currentUser instanceof User || $journal->getUser()?->getId() !== $currentUser->getId()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('advice_seen' . $journal->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_journal_index');
        }

        if ($journal->getPatientAdviceSeenAt() === null) {
            $journal->setPatientAdviceSeenAt(new \DateTime());
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_journal_index', [
            'open_advice' => $journal->getId(),
        ]);
    }

    #[Route('/{id}/music-generate', name: 'app_journal_music_generate', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function generateMusicPrescription(
        Request $request,
        int $id,
        JournalRepository $journalRepository,
        MusicTherapyService $musicTherapyService,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');
        if ($redirect = $this->assertPatientArea()) {
            return $redirect;
        }

        $journal = $journalRepository->find($id);
        if (!$journal) {
            $this->addFlash('error', 'Journal introuvable.');
            return $this->redirectToRoute('app_journal_index');
        }

        $currentUser = $this->getUser();
        if (!$currentUser instanceof User || $journal->getUser()?->getId() !== $currentUser->getId()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('music_generate' . $journal->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_journal_index');
        }

        /** @var array{objective?:string, source?:string, generatedAt?:string, tracks?:array<int,array{title:string,artist:string,url:string}>} $musicPack */
        $musicPack = $musicTherapyService->generateForJournal($journal);
        $journal->setMusicPrescriptionData($musicPack);
        $journal->setMusicPrescriptionObjective($musicPack['objective'] ?? null);
        $journal->setMusicPrescriptionSource($musicPack['source'] ?? null);
        $journal->markMusicPrescriptionGeneratedAt(new \DateTimeImmutable());
        $entityManager->flush();

        $this->addFlash('success', 'Prescription musicale IA générée.');

        return $this->redirectToRoute('app_journal_index', [
            'open_music' => $journal->getId(),
        ]);
    }

    #[Route('/new', name: 'app_journal_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        MusicTherapyService $musicTherapyService
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $this->assertPatientArea();

        $journal = new Journal();
        $form = $this->createForm(JournalType::class, $journal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $currentUser = $this->getUser();
            $journal->setUser($currentUser instanceof User ? $currentUser : null);
            $journal->markCreatedAt(new \DateTimeImmutable());
            $journal->setInputMode('text');

            $entityManager->persist($journal);
            /** @var array{objective?:string, source?:string, generatedAt?:string, tracks?:array<int,array{title:string,artist:string,url:string}>} $musicPack */
            $musicPack = $musicTherapyService->generateForJournal($journal);
            $journal->setMusicPrescriptionData($musicPack);
            $journal->setMusicPrescriptionObjective($musicPack['objective'] ?? null);
            $journal->setMusicPrescriptionSource($musicPack['source'] ?? null);
            $journal->markMusicPrescriptionGeneratedAt(new \DateTimeImmutable());
            $entityManager->flush();

            return $this->redirectToRoute('app_journal_index');
        }

        return $this->render('journal/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/new-vocal', name: 'app_journal_new_voice', methods: ['GET', 'POST'])]
    public function newVoice(
        Request $request,
        EntityManagerInterface $entityManager,
        MusicTherapyService $musicTherapyService
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $this->assertPatientArea();

        $form = $this->createForm(VoiceJournalType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $audio */
            $audio = $form->get('voiceNote')->getData();
            $projectDirParam = $this->getParameter('kernel.project_dir');
            $projectDir = is_string($projectDirParam) ? $projectDirParam : '';
            $uploadDir = $projectDir . '/public/uploads/voice_journals';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0775, true);
            }

            $safeName = 'voice_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4));
            $ext = $audio->guessExtension() ?: 'bin';
            $fileName = $safeName . '.' . $ext;
            $audio->move($uploadDir, $fileName);

            $journal = new Journal();
            $currentUser = $this->getUser();
            $journal->setUser($currentUser instanceof User ? $currentUser : null);
            $journal->markCreatedAt(new \DateTimeImmutable());
            $journal->setContenu('Note vocale envoyée par le patient. En attente de description du psychologue.');
            $journal->setHumeur('calme');
            $journal->setInputMode('voice');
            $journal->setAudioFileName($fileName);
            $journal->setTranscriptionProvider('none');

            $entityManager->persist($journal);
            /** @var array{objective?:string, source?:string, generatedAt?:string, tracks?:array<int,array{title:string,artist:string,url:string}>} $musicPack */
            $musicPack = $musicTherapyService->generateForJournal($journal);
            $journal->setMusicPrescriptionData($musicPack);
            $journal->setMusicPrescriptionObjective($musicPack['objective'] ?? null);
            $journal->setMusicPrescriptionSource($musicPack['source'] ?? null);
            $journal->markMusicPrescriptionGeneratedAt(new \DateTimeImmutable());
            $entityManager->flush();

            $this->addFlash('success', 'Journal vocal envoyé. Le psychologue a reçu une alerte.');
            return $this->redirectToRoute('app_journal_index');
        }

        return $this->render('journal/new_voice.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_journal_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        int $id,
        JournalRepository $journalRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $this->assertPatientArea();

        $journal = $journalRepository->find($id);
        if (!$journal) {
            $this->addFlash('error', 'Journal introuvable.');
            return $this->redirectToRoute('app_journal_index');
        }

        $currentUser = $this->getUser();
        if (!$currentUser instanceof User || $journal->getUser()?->getId() !== $currentUser->getId()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(JournalType::class, $journal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('app_journal_index');
        }

        return $this->render('journal/edit.html.twig', [
            'form' => $form,
            'journal' => $journal,
        ]);
    }

    #[Route('/{id}', name: 'app_journal_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(
        Request $request,
        int $id,
        JournalRepository $journalRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $this->assertPatientArea();

        $journal = $journalRepository->find($id);
        if (!$journal) {
            $this->addFlash('error', 'Journal introuvable ou déjà supprimé.');
            return $this->redirectToRoute('app_journal_index');
        }

        $currentUser = $this->getUser();
        if (!$currentUser instanceof User || $journal->getUser()?->getId() !== $currentUser->getId()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete' . $journal->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($journal);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_journal_index');
    }

    #[Route('/stat', name: 'app_journal_stat', methods: ['GET'])]
    public function stat(JournalRepository $journalRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $this->assertPatientArea();

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $isPsychologue = $this->isGranted('ROLE_PSYCHOLOGUE') || $this->isGranted('ROLE_PSY');

        if ($isPsychologue) {
            $stats = $journalRepository->countByHumeurAll();
            $insights = []; // Pour les psychologues, pas d'insights personnels
        } else {
            $stats = $journalRepository->countByHumeurForUser($user);
            $insights = $this->buildClinicalInsights($journalRepository, $user);
        }

        return $this->render('journal/stat.html.twig', [
            'stats' => $stats,
            'insights' => $insights,
        ]);
    }

    #[Route('/stat/pdf', name: 'app_journal_stat_pdf', methods: ['GET'])]
    public function statPdf(JournalRepository $journalRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $this->assertPatientArea();

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $isPsychologue = $this->isGranted('ROLE_PSYCHOLOGUE') || $this->isGranted('ROLE_PSY');

        if ($isPsychologue) {
            $stats = $journalRepository->countByHumeurAll();
            $insights = [];
        } else {
            $stats = $journalRepository->countByHumeurForUser($user);
            $insights = $this->buildClinicalInsights($journalRepository, $user);
        }

        $total = ($stats['heureux'] ?? 0) + ($stats['calme'] ?? 0) + ($stats['SOS'] ?? 0) + ($stats['en colere'] ?? 0);

        $rows = [
            ['label' => 'Heureux', 'key' => 'heureux', 'color' => '#1f9d55'],
            ['label' => 'Calme', 'key' => 'calme', 'color' => '#2f80ed'],
            ['label' => 'SOS', 'key' => 'SOS', 'color' => '#f2994a'],
            ['label' => 'En colere', 'key' => 'en colere', 'color' => '#e63946'],
        ];

        $projectDirParam = $this->getParameter('kernel.project_dir');
        $projectDir = is_string($projectDirParam) ? $projectDirParam : '';
        $logoPath = $projectDir . '/public/images/logo.png';
        $logoDataUri = null;
        if (is_file($logoPath)) {
            // Ensure we call the global function; provide a safe fallback if the extension is missing
            $mime = \function_exists('mime_content_type')
                ? (\mime_content_type($logoPath) ?: 'image/png')
                : 'image/png';
            $logoDataUri = 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($logoPath));
        }

        $html = $this->renderView('journal/stat_pdf.html.twig', [
            'generatedAt' => new \DateTime(),
            'stats' => $stats,
            'total' => $total,
            'rows' => $rows,
            'user' => $user,
            'logoDataUri' => $logoDataUri,
            'insights' => $insights,
        ]);

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->setDefaultFont('DejaVu Sans');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename=\"statistiques_journal.pdf\"',
            ]
        );
    }

    #[Route('/pdf', name: 'app_journal_pdf', methods: ['GET'])]
    public function journalPdf(JournalRepository $journalRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $this->assertPatientArea();

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $journals = $journalRepository->searchAndSortByUser($user, '', 'recent');

        $html = $this->renderView('journal/journal_pdf.html.twig', [
            'journals' => $journals,
            'user' => $user,
            'generatedAt' => new \DateTime(),
        ]);

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->setDefaultFont('DejaVu Sans');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename=\"rapports_journaux_' . $user->getPrenom() . '_' . $user->getNom() . '.pdf\"',
            ]
        );
    }

    /**
     * @return array{
     *   trend: array{label:string,tone:string,icon:string,delta:float,recentRate:float,previousRate:float},
     *   priority: array{label:string,tone:string,reason:string},
     *   windows: array{recentLabel:string,previousLabel:string}
     * }
     */
    private function buildClinicalInsights(JournalRepository $journalRepository, User $user): array
    {
        $now = new \DateTimeImmutable();
        $recentStart = $now->modify('-7 days');
        $previousStart = $now->modify('-14 days');

        $recentStats = $journalRepository->countByHumeurForUserBetween($user, $recentStart, $now);
        $previousStats = $journalRepository->countByHumeurForUserBetween($user, $previousStart, $recentStart);

        $recentRate = $this->computeDistressRate($recentStats);
        $previousRate = $this->computeDistressRate($previousStats);
        $delta = $recentRate - $previousRate;

        $trend = [
            'label' => 'Stable',
            'tone' => 'stable',
            'icon' => '>',
            'delta' => $delta,
            'recentRate' => $recentRate,
            'previousRate' => $previousRate,
        ];

        if ($delta <= -8.0) {
            $trend['label'] = 'Amelioration';
            $trend['tone'] = 'positive';
            $trend['icon'] = 'UP';
        } elseif ($delta >= 8.0) {
            $trend['label'] = 'Risque';
            $trend['tone'] = 'negative';
            $trend['icon'] = 'ALERT';
        }

        $sosRecent = (int) ($recentStats['SOS'] ?? 0);
        $angerRecent = (int) ($recentStats['en colere'] ?? 0);

        $priority = [
            'label' => 'Normal',
            'tone' => 'normal',
            'reason' => 'Charge emotionnelle faible a moderee.',
        ];

        if ($sosRecent >= 3 || $recentRate >= 55.0) {
            $priority = [
                'label' => 'Urgent',
                'tone' => 'urgent',
                'reason' => 'Frequence SOS ou intensite emotionnelle elevee sur 7 jours.',
            ];
        } elseif ($sosRecent >= 1 || $angerRecent >= 2 || $recentRate >= 35.0) {
            $priority = [
                'label' => 'Surveillance',
                'tone' => 'watch',
                'reason' => 'Signaux de vigilance detectes sur la semaine recente.',
            ];
        }

        return [
            'trend' => $trend,
            'priority' => $priority,
            'windows' => [
                'recentLabel' => $recentStart->format('d/m') . ' - ' . $now->format('d/m'),
                'previousLabel' => $previousStart->format('d/m') . ' - ' . $recentStart->format('d/m'),
            ],
        ];
    }

    /**
     * @param array{heureux?:int,calme?:int,SOS?:int,'en colere'?:int} $stats
     */
    private function computeDistressRate(array $stats): float
    {
        $heureux = (int) ($stats['heureux'] ?? 0);
        $calme = (int) ($stats['calme'] ?? 0);
        $sos = (int) ($stats['SOS'] ?? 0);
        $anger = (int) ($stats['en colere'] ?? 0);

        $total = $heureux + $calme + $sos + $anger;
        if ($total === 0) {
            return 0.0;
        }

        $weighted = ($sos * 3) + ($anger * 2) + ($calme * 1);
        $max = $total * 3;

        return round(($weighted / $max) * 100, 1);
    }
}
