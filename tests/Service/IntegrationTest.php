<?php

namespace App\Tests\Service;

use App\Entity\TestAdaptatif;
use App\Entity\User;
use App\Service\AnalyseEmotionnelleService;
use App\Service\GroqAiService;
use App\Service\QuestionnaireAdaptatifService;
use App\Service\ScoreCalculatorService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class IntegrationTest extends TestCase
{
    private QuestionnaireAdaptatifService $questionnaireService;
    private AnalyseEmotionnelleService $analyseService;
    private ScoreCalculatorService $scoreCalculator;
    private GroqAiService $groqService;

    protected function setUp(): void
    {
        // Mocks pour les dépendances
        $httpClient = $this->createMock(HttpClientInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        // Configuration du mock HTTP pour simuler l'API Groq
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('toArray')->willReturn([
            'choices' => [
                [
                    'message' => [
                        'content' => '{"question": "Comment évaluez-vous votre niveau de stress actuel ?", "options": ["Faible", "Modéré", "Élevé"], "valeurs": [1, 2, 3]}'
                    ]
                ]
            ]
        ]);

        $httpClient->method('request')->willReturn($mockResponse);

        // Initialisation des services
        $this->groqService = new GroqAiService($httpClient, $logger, 'test-api-key');
        $this->questionnaireService = new QuestionnaireAdaptatifService($this->groqService, $logger);
        $this->analyseService = new AnalyseEmotionnelleService();
        $this->scoreCalculator = new ScoreCalculatorService();
    }

    public function testWorkflowCompletTestAdaptatif(): void
    {
        // Création d'un test adaptatif
        $test = $this->createMock(TestAdaptatif::class);
        $user = $this->createMock(User::class);

        // Configuration du test
        $test->method('getCategorie')->willReturn('stress');
        $test->method('isTermine')->willReturn(false);
        $test->method('getProfilPatient')->willReturn(['age' => 30, 'genre' => 'F']);

        // Étape 1: Génération de la première question
        $test->method('getQuestionsReponses')->willReturn([]);
        $test->method('getScoreActuel')->willReturn(0);

        $question1 = $this->questionnaireService->genererProchaineQuestion($test);
        $this->assertIsArray($question1);
        $this->assertArrayHasKey('question', $question1);
        $this->assertArrayHasKey('options', $question1);
        $this->assertArrayHasKey('valeurs', $question1);

        // Simulation de la réponse du patient
        $reponse1 = [
            'question' => $question1['question'],
            'reponse' => $question1['options'][1], // Réponse modérée
            'valeur' => $question1['valeurs'][1],  // Valeur 2
            'timestamp' => date('Y-m-d H:i:s')
        ];

        // Étape 2: Génération de la deuxième question avec historique
        $test->method('getQuestionsReponses')->willReturn([$reponse1]);
        $test->method('getScoreActuel')->willReturn(2);

        $question2 = $this->questionnaireService->genererProchaineQuestion($test);
        $this->assertIsArray($question2);
        $this->assertNotEquals($question1['question'], $question2['question']); // Question différente

        // Simulation de plusieurs réponses pour créer un historique complet
        $questionsReponses = [
            $reponse1,
            [
                'question' => $question2['question'],
                'reponse' => $question2['options'][2], // Réponse élevée
                'valeur' => $question2['valeurs'][2],  // Valeur 3
                'timestamp' => date('Y-m-d H:i:s', strtotime('+1 minute'))
            ],
            [
                'question' => 'Question 3',
                'reponse' => 'Réponse 3',
                'valeur' => 2,
                'timestamp' => date('Y-m-d H:i:s', strtotime('+2 minutes'))
            ]
        ];

        // Étape 3: Analyse de l'évolution émotionnelle
        $test->method('getQuestionsReponses')->willReturn($questionsReponses);

        $analyse = $this->analyseService->analyserEvolution($test);
        $this->assertIsArray($analyse);
        $this->assertArrayHasKey('chronologie', $analyse);
        $this->assertArrayHasKey('statistiques', $analyse);
        $this->assertArrayHasKey('tendances', $analyse);
        $this->assertCount(3, $analyse['chronologie']);

        // Vérification des statistiques
        $this->assertEquals(2.33, round($analyse['statistiques']['scoreMoyen'], 2));
        $this->assertEquals(3, $analyse['statistiques']['scoreMax']);
        $this->assertEquals(2, $analyse['statistiques']['scoreMin']);

        // Étape 4: Calcul du score total
        $test->method('getScoreTotal')->willReturn(7);
        $test->method('getNombreQuestions')->willReturn(3);

        // Étape 5: Génération de l'analyse finale
        $analyseFinale = $this->questionnaireService->genererAnalyseFinale($test);
        $this->assertIsString($analyseFinale);
        $this->assertNotEmpty($analyseFinale);

        // Étape 6: Interprétation du score
        $interpretation = $this->scoreCalculator->interpretScore(7, 'stress');
        $this->assertIsArray($interpretation);
        $this->assertArrayHasKey('niveau', $interpretation);
        $this->assertArrayHasKey('couleur', $interpretation);
        $this->assertArrayHasKey('message', $interpretation);
        $this->assertEquals('Élevé', $interpretation['niveau']);
        $this->assertEquals('danger', $interpretation['couleur']);
    }

    public function testWorkflowTestArretAnticipe(): void
    {
        // Test avec des réponses indiquant un arrêt anticipé
        $test = $this->createMock(TestAdaptatif::class);
        $test->method('getCategorie')->willReturn('stress');
        $test->method('isTermine')->willReturn(false);

        // Réponses très faibles indiquant un arrêt anticipé
        $questionsReponses = [
            ['question' => 'Q1', 'reponse' => 'Très bien', 'valeur' => 1],
            ['question' => 'Q2', 'reponse' => 'Parfait', 'valeur' => 1],
            ['question' => 'Q3', 'reponse' => 'Excellent', 'valeur' => 1]
        ];

        $test->method('getQuestionsReponses')->willReturn($questionsReponses);
        $test->method('getScoreActuel')->willReturn(3);

        // Le service devrait retourner null (arrêt anticipé)
        $questionSuivante = $this->questionnaireService->genererProchaineQuestion($test);
        $this->assertNull($questionSuivante);
    }

    public function testWorkflowTestScoreCritique(): void
    {
        // Test avec des scores critiques
        $test = $this->createMock(TestAdaptatif::class);
        $test->method('getCategorie')->willReturn('depression');
        $test->method('isTermine')->willReturn(false);

        // 5 réponses critiques (valeur 3)
        $questionsReponses = array_fill(0, 5, ['question' => 'Q', 'reponse' => 'R', 'valeur' => 3]);
        $test->method('getQuestionsReponses')->willReturn($questionsReponses);
        $test->method('getScoreActuel')->willReturn(15);

        // Le service devrait retourner null (arrêt à cause du score critique)
        $questionSuivante = $this->questionnaireService->genererProchaineQuestion($test);
        $this->assertNull($questionSuivante);
    }

    public function testExtractionProfilPatient(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getEmail')->willReturn('test@example.com');
        $user->method('getNom')->willReturn('Dupont');
        $user->method('getPrenom')->willReturn('Jean');
        $user->method('getDateNaissance')->willReturn(new \DateTime('1990-01-01'));

        $profil = $this->questionnaireService->extraireProfilPatient($user);

        $this->assertIsArray($profil);
        $this->assertEquals('test@example.com', $profil['email']);
        $this->assertEquals('Dupont', $profil['nom']);
        $this->assertEquals('Jean', $profil['prenom']);
        $this->assertGreaterThan(30, $profil['age']); // Age calculé
    }

    public function testCalculScoreParCategories(): void
    {
        $questions = [
            ['categorie' => 'stress', 'texte' => 'Q1'],
            ['categorie' => 'stress', 'texte' => 'Q2'],
            ['categorie' => 'depression', 'texte' => 'Q3'],
            ['categorie' => 'iq', 'texte' => 'Q4']
        ];

        $reponses = [
            ['question_id' => 0, 'valeur' => 2], // stress
            ['question_id' => 1, 'valeur' => 3], // stress
            ['question_id' => 2, 'valeur' => 1], // depression
            ['question_id' => 3, 'valeur' => 2]  // iq
        ];

        $result = $this->scoreCalculator->calculateScoreByCategory($questions, $reponses);

        $this->assertEquals(5, $result['stress']['score']); // 2 + 3
        $this->assertEquals(2, $result['stress']['count']);
        $this->assertEquals(1, $result['depression']['score']);
        $this->assertEquals(1, $result['depression']['count']);
        $this->assertEquals(2, $result['iq']['score']);
        $this->assertEquals(1, $result['iq']['count']);
    }

    public function testAnalyseEmotionnelleComplete(): void
    {
        $test = $this->createMock(TestAdaptatif::class);

        $questionsReponses = [
            ['question' => 'Q1', 'reponse' => 'Stressé', 'valeur' => 3, 'timestamp' => '2024-01-01 10:00:00'],
            ['question' => 'Q2', 'reponse' => 'Anxieux', 'valeur' => 2, 'timestamp' => '2024-01-01 10:05:00'],
            ['question' => 'Q3', 'reponse' => 'Préoccupé', 'valeur' => 3, 'timestamp' => '2024-01-01 10:10:00'],
            ['question' => 'Q4', 'reponse' => 'Calme', 'valeur' => 1, 'timestamp' => '2024-01-01 10:15:00']
        ];

        $test->method('getQuestionsReponses')->willReturn($questionsReponses);

        $result = $this->analyseService->analyserEvolution($test);

        // Vérifications complètes
        $this->assertCount(4, $result['chronologie']);
        $this->assertEquals(9, $result['chronologie'][3]['scoreCumule']); // 3+2+3+1

        $this->assertEquals(2.25, round($result['statistiques']['scoreMoyen'], 2));
        $this->assertEquals(3, $result['statistiques']['scoreMax']);
        $this->assertEquals(1, $result['statistiques']['scoreMin']);

        $this->assertIsArray($result['graphiqueData']);
        $this->assertCount(4, $result['graphiqueData']['labels']);
        $this->assertEquals([3, 2, 3, 1], $result['graphiqueData']['datasets'][0]['data']);
    }
}