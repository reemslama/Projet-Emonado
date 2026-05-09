<?php

namespace App\Controller;

use App\Entity\RendezVous;
use App\Entity\User;
use App\Service\PsychologuePreparationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/psychologue/preparation')]
final class PsychologuePreparationController extends AbstractController
{
    private function assertPsyArea(): void
    {
        if (!$this->isGranted('ROLE_PSYCHOLOGUE') && !$this->isGranted('ROLE_PSY') && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }
    }

    #[Route('', name: 'psychologue_preparation', methods: ['GET'])]
    public function index(Request $request, PsychologuePreparationService $prepService): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $this->assertPsyArea();

        $psy = $this->getUser();
        if (!$psy instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $allRows = $prepService->buildRowsForPsychologue($psy);
        $stats = $prepService->countStats($allRows);
        $patients = $prepService->distinctPatients($allRows);

        $patientFilter = $request->query->getInt('patient', 0);
        $patientFilter = $patientFilter > 0 ? $patientFilter : null;

        $sort = (string) $request->query->get('tri', 'grave');
        $sort = in_array($sort, ['grave', 'recent', 'ancien'], true) ? $sort : 'grave';

        $search = (string) $request->query->get('q', '');

        $rows = $prepService->filterAndSortRows($allRows, $patientFilter, $sort, $search);

        $selectedRdvId = $request->query->getInt('rdv', 0);
        $selected = null;
        if ($selectedRdvId > 0) {
            foreach ($rows as $row) {
                /** @var RendezVous $rdv */
                $rdv = $row['rdv'];
                if ($rdv->getId() === $selectedRdvId) {
                    $selected = $row;
                    break;
                }
            }
        }
        if ($selected === null && $rows !== []) {
            $selected = $rows[0];
        }

        $chartPayload = null;
        if ($selected !== null && ($selected['questionnaire'] ?? null) !== null) {
            $pred = $selected['prediction'];
            $chartPayload = [
                'labels' => $pred['chart_labels'],
                'values' => $pred['chart_values'],
                'colors' => $pred['chart_colors'],
            ];
        }

        return $this->render('psychologue/preparation.html.twig', [
            'rows' => $rows,
            'selected' => $selected,
            'stats' => $stats,
            'patients' => $patients,
            'patient_filter' => $patientFilter,
            'sort' => $sort,
            'search' => $search,
            'chart_payload_json' => $chartPayload !== null ? json_encode($chartPayload, JSON_THROW_ON_ERROR) : null,
        ]);
    }
}
