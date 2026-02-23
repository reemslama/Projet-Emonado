<?php

namespace App\Controller;

use App\Entity\TestAdaptatif;
use App\Repository\TestAdaptatifRepository;
use App\Service\QuestionnaireAdaptatifService;
use App\Service\AnalyseEmotionnelleService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/test-adaptatif')]
class TestAdaptatifController extends AbstractController
{
    public function __construct(
        private QuestionnaireAdaptatifService $questionnaireService,
        private TestAdaptatifRepository $testRepository,
        private AnalyseEmotionnelleService $analyseEmotionnelleService
    ) {}

    #[Route('/demarrer/{categorie}', name: 'app_test_adaptatif_start')]
    public function demarrer(string $categorie): Response
    {
        // Créer une nouvelle session de test
        $test = new TestAdaptatif();
        $test->setCategorie($categorie);
        
        // Ajouter le profil du patient si connecté
        $user = $this->getUser();
        if ($user) {
            $test->setPatient($user);
            $profil = $this->questionnaireService->extraireProfilPatient($user);
            $test->setProfilPatient($profil);
        }

        $this->testRepository->save($test, true);

        // Stocker l'ID du test en session
        $this->container->get('request_stack')->getSession()->set('test_adaptatif_id', $test->getId());

        return $this->redirectToRoute('app_test_adaptatif_question');
    }

    #[Route('/question', name: 'app_test_adaptatif_question')]
    public function question(): Response
    {
        $testId = $this->container->get('request_stack')->getSession()->get('test_adaptatif_id');
        
        if (!$testId) {
            $this->addFlash('error', 'Session de test introuvable. Veuillez recommencer.');
            return $this->redirectToRoute('test_page');
        }

        $test = $this->testRepository->find($testId);
        
        if (!$test) {
            $this->addFlash('error', 'Test introuvable.');
            return $this->redirectToRoute('test_page');
        }

        // Générer la prochaine question
        $prochaine = $this->questionnaireService->genererProchaineQuestion($test);

        // Si plus de question, terminer le test
        if (!$prochaine) {
            return $this->redirectToRoute('app_test_adaptatif_resultat');
        }

        return $this->render('test_adaptatif/question.html.twig', [
            'test' => $test,
            'question' => $prochaine,
            'numeroQuestion' => count($test->getQuestionsReponses()) + 1,
            'categorie' => $test->getCategorie(),
        ]);
    }

    #[Route('/repondre', name: 'app_test_adaptatif_answer', methods: ['POST'])]
    public function repondre(Request $request): Response
    {
        $testId = $this->container->get('request_stack')->getSession()->get('test_adaptatif_id');
        
        if (!$testId) {
            return $this->json(['error' => 'Session introuvable'], 400);
        }

        $test = $this->testRepository->find($testId);
        
        if (!$test) {
            return $this->json(['error' => 'Test introuvable'], 404);
        }

        // Récupérer la réponse
        $questionTexte = $request->request->get('question');
        $reponseTexte = $request->request->get('reponse');
        $valeur = (int) $request->request->get('valeur');

        // Enregistrer la réponse
        $test->ajouterQuestionReponse($questionTexte, $reponseTexte, $valeur);
        $this->testRepository->save($test, true);

        // Rediriger vers la prochaine question
        return $this->redirectToRoute('app_test_adaptatif_question');
    }

    #[Route('/resultat/{id}', name: 'app_test_adaptatif_resultat', requirements: ['id' => '\\d+'])]
    public function resultat(?int $id = null): Response
    {
        // Si un ID est fourni en paramètre (depuis l'historique), on l'utilise
        // Sinon, on récupère depuis la session (flux normal de fin de test)
        $testId = $id ?? $this->container->get('request_stack')->getSession()->get('test_adaptatif_id');
        
        if (!$testId) {
            $this->addFlash('error', 'Session de test introuvable.');
            return $this->redirectToRoute('test_page');
        }

        $test = $this->testRepository->find($testId);
        
        if (!$test) {
            $this->addFlash('error', 'Test introuvable.');
            return $this->redirectToRoute('test_page');
        }

        // Vérification de sécurité : si accès depuis l'historique (ID fourni),
        // s'assurer que l'utilisateur connecté est bien le propriétaire du test
        if ($id && $this->getUser() && $test->getPatient() !== $this->getUser()) {
            $this->addFlash('error', 'Accès non autorisé à ce test.');
            return $this->redirectToRoute('app_test_adaptatif_historique');
        }

        // Marquer le test comme terminé (uniquement si c'est un nouveau test, pas un affichage depuis l'historique)
        if (!$test->isTermine() && !$id) {
            $test->setTermine(true);
            
            // Générer l'analyse finale
            $analyse = $this->questionnaireService->genererAnalyseFinale($test);
            $test->setAnalyse($analyse);
            
            $this->testRepository->save($test, true);
        }

        // Générer l'analyse émotionnelle avancée
        $analyseEmotionnelle = $this->analyseEmotionnelleService->analyserEvolution($test);

        // Nettoyer la session (uniquement si c'est un nouveau test)
        if (!$id) {
            $this->container->get('request_stack')->getSession()->remove('test_adaptatif_id');
        }

        return $this->render('test_adaptatif/resultat.html.twig', [
            'test' => $test,
            'analyse' => $test->getAnalyse(),
            'analyseEmotionnelle' => $analyseEmotionnelle,
        ]);
    }

    #[Route('/historique', name: 'app_test_adaptatif_historique')]
    public function historique(): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour voir votre historique.');
            return $this->redirectToRoute('app_login');
        }

        $tests = $this->testRepository->findBy(
            ['patient' => $user, 'termine' => true],
            ['dateFin' => 'DESC']
        );

        return $this->render('test_adaptatif/historique.html.twig', [
            'tests' => $tests,
        ]);
    }
}
