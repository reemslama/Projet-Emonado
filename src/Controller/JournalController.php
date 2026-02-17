<?php

namespace App\Controller;

use App\Entity\Journal;
use App\Form\JournalType;
use App\Repository\JournalRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Dompdf\Dompdf;
use Dompdf\Options;

#[Route('/journal')]
class JournalController extends AbstractController
{
    #[Route('/', name: 'app_journal_index', methods: ['GET'])]
    public function index(
        Request $request,
        JournalRepository $journalRepository
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $keyword = $request->query->get('q', '');
        $sort    = $request->query->get('sort', 'recent');

        // 🧑‍⚕️ / 👑 Vue globale pour l'admin et le psychologue
        if (
            $this->isGranted('ROLE_ADMIN')
            || $this->isGranted('ROLE_PSYCHOLOGUE')
            || $this->isGranted('ROLE_PSY')
        ) {
            $journals = $journalRepository->searchAndSortAll(
                $keyword,
                $sort
            );

            $stats = $journalRepository->countByHumeurAll();
        } else {
            // 👤 Vue restreinte au propriétaire du journal (patient)
            $user = $this->getUser();

            $journals = $journalRepository->searchAndSortByUser(
                $user,
                $keyword,
                $sort
            );

            $stats = $journalRepository->countByHumeurForUser($user);
        }

        return $this->render('journal/index.html.twig', [
            'journals' => $journals,
            'stats'    => $stats,
            'keyword'  => $keyword,
            'sort'     => $sort,
        ]);
    }

    #[Route('/new', name: 'app_journal_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $journal = new Journal();
        $form = $this->createForm(JournalType::class, $journal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $journal->setUser($this->getUser());
            $journal->setDateCreation(new \DateTime());

            $entityManager->persist($journal);
            $entityManager->flush();

            return $this->redirectToRoute('app_journal_index');
        }

        return $this->render('journal/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_journal_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Journal $journal,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if ($journal->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(JournalType::class, $journal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_journal_index');
        }

        return $this->render('journal/edit.html.twig', [
            'form'    => $form,
            'journal' => $journal,
        ]);
    }

    #[Route('/{id}', name: 'app_journal_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Journal $journal,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if ($journal->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid(
            'delete' . $journal->getId(),
            $request->request->get('_token')
        )) {
            $entityManager->remove($journal);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_journal_index');
    }
 #[Route('/stat', name: 'app_journal_stat', methods: ['GET'])]
public function stat(JournalRepository $journalRepository): Response
{
    $this->denyAccessUnlessGranted('ROLE_USER');

    $user = $this->getUser();

    $stats = $journalRepository->countByHumeurForUser($user);

    return $this->render('journal/stat.html.twig', [
        'stats' => $stats,
    ]);
}

    #[Route('/stat/pdf', name: 'app_journal_stat_pdf', methods: ['GET'])]
    public function statPdf(JournalRepository $journalRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();
        $stats = $journalRepository->countByHumeurForUser($user);
        $total = ($stats['heureux'] ?? 0) + ($stats['calme'] ?? 0) + ($stats['SOS'] ?? 0) + ($stats['en colere'] ?? 0);

        $percent = function($count) use ($total) {
            if ($total === 0) { return '0.0%'; }
            return number_format(($count / $total) * 100, 1, '.', ' ') . '%';
        };

        $html = '<html><head><meta charset="UTF-8"><style>
            body{font-family: DejaVu Sans, Arial, sans-serif;}
            h2{color:#198754;margin:0 0 16px 0}
            table{width:100%;border-collapse:collapse;margin-top:12px}
            th,td{border:1px solid #ddd;padding:8px;text-align:left}
            th{background:#f6f8fa}
            .small{color:#6b7280;font-size:12px}
        </style></head><body>';
        $html .= '<h2>Statistiques des humeurs</h2>';
        $html .= '<div class="small">Généré le ' . (new \DateTime())->format('d/m/Y H:i') . '</div>';
        $html .= '<table><thead><tr><th>Humeur</th><th>Nombre</th><th>Pourcentage</th></tr></thead><tbody>';
        $rows = [
            ['label' => 'Heureux', 'key' => 'heureux'],
            ['label' => 'Calme', 'key' => 'calme'],
            ['label' => 'SOS', 'key' => 'SOS'],
            ['label' => 'En colère', 'key' => 'en colere'],
        ];
        foreach ($rows as $r) {
            $count = $stats[$r['key']] ?? 0;
            $html .= '<tr><td>' . $r['label'] . '</td><td>' . $count . '</td><td>' . $percent($count) . '</td></tr>';
        }
        $html .= '<tr><th>Total</th><th colspan="2">' . $total . '</th></tr>';
        $html .= '</tbody></table>';
        $html .= '</body></html>';

        $options = new Options();
        $options->set('isRemoteEnabled', true);
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
                'Content-Disposition' => 'attachment; filename="statistiques_journal.pdf"'
            ]
        );
    }
}
