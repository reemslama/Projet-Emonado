<?php

namespace App\Controller;

use App\Entity\TestAdaptatif;
use App\Repository\TestAdaptatifRepository;
use App\Service\ScoreCalculatorService;
use Knp\Bundle\SnappyBundle\Snappy\Response\PdfResponse;
use Knp\Snappy\Pdf;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/test/pdf')]
class TestPdfController extends AbstractController
{
    private Pdf $knpSnappyPdf;
    private ScoreCalculatorService $scoreCalculator;

    public function __construct(
        Pdf $knpSnappyPdf,
        ScoreCalculatorService $scoreCalculator
    ) {
        $this->knpSnappyPdf = $knpSnappyPdf;
        $this->scoreCalculator = $scoreCalculator;
    }

    /**
     * Télécharger le résultat d'un test en PDF
     */
    #[Route('/{id}/telecharger', name: 'test_pdf_download', methods: ['GET'])]
    public function telecharger(int $id, TestAdaptatifRepository $repository): Response
    {
        $test = $repository->find($id);
        
        if (!$test) {
            throw $this->createNotFoundException('Test non trouvé');
        }

        // Vérifier les permissions (le patient ne peut voir que ses propres tests)
        $this->denyAccessUnlessGranted('view', $test);

        // Calculer les statistiques
        $interpretation = $this->scoreCalculator->interpretScore(
            $test->getScoreActuel(),
            $test->getCategorie()
        );

        $scoreParCategorie = $this->scoreCalculator->analyzeQuestionsReponses(
            $test->getQuestionsReponses()
        );

        // Rendre le template HTML
        $html = $this->renderView('test/pdf_resultat.html.twig', [
            'test' => $test,
            'interpretation' => $interpretation,
            'scoreParCategorie' => $scoreParCategorie,
            'dateGeneration' => new \DateTimeImmutable(),
        ]);

        // Générer le PDF
        $filename = sprintf(
            'test_%s_%s.pdf',
            $test->getCategorie(),
            $test->getDateDebut()->format('Y-m-d')
        );

        return new PdfResponse(
            $this->knpSnappyPdf->getOutputFromHtml($html, [
                'encoding' => 'UTF-8',
                'enable-local-file-access' => true,
                'margin-top' => 10,
                'margin-right' => 10,
                'margin-bottom' => 10,
                'margin-left' => 10,
            ]),
            $filename
        );
    }

    /**
     * Prévisualiser le PDF dans le navigateur (HTML)
     */
    #[Route('/{id}/previsualiser', name: 'test_pdf_preview', methods: ['GET'])]
    public function previsualiser(int $id, TestAdaptatifRepository $repository): Response
    {
        $test = $repository->find($id);
        
        if (!$test) {
            throw $this->createNotFoundException('Test non trouvé');
        }

        $this->denyAccessUnlessGranted('view', $test);

        // Calculer les statistiques
        $interpretation = $this->scoreCalculator->interpretScore(
            $test->getScoreActuel(),
            $test->getCategorie()
        );

        $scoreParCategorie = $this->scoreCalculator->analyzeQuestionsReponses(
            $test->getQuestionsReponses()
        );

        return $this->render('test/pdf_resultat.html.twig', [
            'test' => $test,
            'interpretation' => $interpretation,
            'scoreParCategorie' => $scoreParCategorie,
            'dateGeneration' => new \DateTimeImmutable(),
            'preview' => true, // Pour afficher l'HTML au lieu de générer le PDF
        ]);
    }

    /**
     * Générer un rapport PDF pour tous les tests d'un patient
     */
    #[Route('/patient/{patientId}/rapport', name: 'test_pdf_rapport_patient', methods: ['GET'])]
    public function rapportPatient(
        int $patientId,
        TestAdaptatifRepository $repository
    ): Response {
        $tests = $repository->findBy(
            ['patient' => $patientId],
            ['dateDebut' => 'DESC']
        );

        if (empty($tests)) {
            throw $this->createNotFoundException('Aucun test trouvé pour ce patient');
        }

        // Vérifier les permissions
        foreach ($tests as $test) {
            $this->denyAccessUnlessGranted('view', $test);
        }

        // Calculer les statistiques pour chaque test
        $statistiques = [];
        foreach ($tests as $test) {
            $statistiques[$test->getId()] = [
                'interpretation' => $this->scoreCalculator->interpretScore(
                    $test->getScoreActuel(),
                    $test->getCategorie()
                ),
                'scoreParCategorie' => $this->scoreCalculator->analyzeQuestionsReponses(
                    $test->getQuestionsReponses()
                ),
            ];
        }

        // Rendre le template HTML
        $html = $this->renderView('test/pdf_rapport_patient.html.twig', [
            'tests' => $tests,
            'patient' => $tests[0]->getPatient(),
            'statistiques' => $statistiques,
            'dateGeneration' => new \DateTimeImmutable(),
        ]);

        // Générer le PDF
        $filename = sprintf(
            'rapport_patient_%d_%s.pdf',
            $patientId,
            (new \DateTimeImmutable())->format('Y-m-d')
        );

        return new PdfResponse(
            $this->knpSnappyPdf->getOutputFromHtml($html, [
                'encoding' => 'UTF-8',
                'enable-local-file-access' => true,
                'margin-top' => 10,
                'margin-right' => 10,
                'margin-bottom' => 10,
                'margin-left' => 10,
                'orientation' => 'Portrait',
                'page-size' => 'A4',
            ]),
            $filename
        );
    }
}
