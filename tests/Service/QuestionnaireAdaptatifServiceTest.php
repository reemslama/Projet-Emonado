<?php

namespace App\Tests\Service;

use App\Entity\TestAdaptatif;
use App\Entity\User;
use App\Service\GroqAiService;
use App\Service\QuestionnaireAdaptatifService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class QuestionnaireAdaptatifServiceTest extends TestCase
{
    private QuestionnaireAdaptatifService $service;
    private GroqAiService&\PHPUnit\Framework\MockObject\MockObject $groqAiService;
    private LoggerInterface&\PHPUnit\Framework\MockObject\MockObject $logger;

    protected function setUp(): void
    {
        $this->groqAiService = $this->createMock(GroqAiService::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new QuestionnaireAdaptatifService(
            $this->groqAiService,
            $this->logger
        );
    }

    public function testGenererProchaineQuestionPremiereQuestion(): void
    {
        $test = $this->createMock(TestAdaptatif::class);
        $test->method('getCategorie')->willReturn('stress');
        $test->method('getQuestionsReponses')->willReturn([]);
        $test->method('getScoreActuel')->willReturn(0);
        $test->method('getProfilPatient')->willReturn([]);

        $this->groqAiService->method('isConfigured')->willReturn(true);
        $this->groqAiService->expects($this->once())
            ->method('genererQuestionPsychologique')
            ->with('stress', [], [], 'initial')
            ->willReturn([
                'question' => 'Comment vous sentez-vous ?',
                'options' => ['Bien', 'Moyen', 'Mal'],
                'valeurs' => [1, 2, 3]
            ]);

        $result = $this->service->genererProchaineQuestion($test);

        $this->assertIsArray($result);
        $this->assertEquals('Comment vous sentez-vous ?', $result['question']);
    }

    public function testGenererProchaineQuestionApprofondissement(): void
    {
        $test = $this->createMock(TestAdaptatif::class);
        $test->method('getCategorie')->willReturn('stress');
        $test->method('getQuestionsReponses')->willReturn([
            ['question' => 'Q1', 'reponse' => 'Mal', 'valeur' => 3]
        ]);
        $test->method('getScoreActuel')->willReturn(3);
        $test->method('getProfilPatient')->willReturn([]);

        $this->groqAiService->method('isConfigured')->willReturn(true);
        $this->groqAiService->expects($this->once())
            ->method('genererQuestionPsychologique')
            ->with('stress', $this->anything(), [], 'approfondissement')
            ->willReturn([
                'question' => 'Pouvez-vous préciser ?',
                'options' => ['Un peu', 'Beaucoup', 'Énormément'],
                'valeurs' => [1, 2, 3]
            ]);

        $result = $this->service->genererProchaineQuestion($test);

        $this->assertIsArray($result);
        $this->assertEquals('Pouvez-vous préciser ?', $result['question']);
    }

    public function testGenererProchaineQuestionArretAnticipeStableFaible(): void
    {
        $test = $this->createMock(TestAdaptatif::class);
        $test->method('getCategorie')->willReturn('stress');
        $test->method('getQuestionsReponses')->willReturn([
            ['question' => 'Q1', 'reponse' => 'Bien', 'valeur' => 1],
            ['question' => 'Q2', 'reponse' => 'Bien', 'valeur' => 1],
            ['question' => 'Q3', 'reponse' => 'Bien', 'valeur' => 1]
        ]);
        $test->method('getScoreActuel')->willReturn(3);
        $test->method('getProfilPatient')->willReturn([]);

        $result = $this->service->genererProchaineQuestion($test);

        $this->assertNull($result); // Test arrêté car tendance stable_faible
    }

    public function testGenererProchaineQuestionDepassementMaxQuestions(): void
    {
        $questionsReponses = array_fill(0, 10, ['question' => 'Q', 'reponse' => 'R', 'valeur' => 2]);

        $test = $this->createMock(TestAdaptatif::class);
        $test->method('getCategorie')->willReturn('stress');
        $test->method('getQuestionsReponses')->willReturn($questionsReponses);
        $test->method('getScoreActuel')->willReturn(20);
        $test->method('getProfilPatient')->willReturn([]);

        $result = $this->service->genererProchaineQuestion($test);

        $this->assertNull($result); // Test arrêté car MAX_QUESTIONS atteint
    }

    public function testGenererProchaineQuestionTestTermine(): void
    {
        $test = $this->createMock(TestAdaptatif::class);
        $test->method('isTermine')->willReturn(true);

        $result = $this->service->genererProchaineQuestion($test);

        $this->assertNull($result); // Test déjà terminé
    }

    public function testGenererProchaineQuestionGroqErrorFallback(): void
    {
        $test = $this->createMock(TestAdaptatif::class);
        $test->method('getCategorie')->willReturn('stress');
        $test->method('getQuestionsReponses')->willReturn([]);
        $test->method('getScoreActuel')->willReturn(0);
        $test->method('getProfilPatient')->willReturn([]);

        $this->groqAiService->method('isConfigured')->willReturn(true);
        $this->groqAiService->expects($this->once())
            ->method('genererQuestionPsychologique')
            ->willThrowException(new \Exception('API Error'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Erreur Groq AI'));

        $result = $this->service->genererProchaineQuestion($test);

        $this->assertIsArray($result); // Fallback activé
        $this->assertArrayHasKey('question', $result);
    }

    public function testGenererProchaineQuestionGroqNotConfigured(): void
    {
        $test = $this->createMock(TestAdaptatif::class);
        $test->method('getCategorie')->willReturn('stress');
        $test->method('getQuestionsReponses')->willReturn([]);
        $test->method('getScoreActuel')->willReturn(0);
        $test->method('getProfilPatient')->willReturn([]);

        $this->groqAiService->method('isConfigured')->willReturn(false);

        $result = $this->service->genererProchaineQuestion($test);

        $this->assertIsArray($result); // Fallback activé
        $this->assertArrayHasKey('question', $result);
    }

    public function testGenererAnalyseFinale(): void
    {
        $test = $this->createMock(TestAdaptatif::class);
        $test->method('getCategorie')->willReturn('stress');
        $test->method('getQuestionsReponses')->willReturn([
            ['question' => 'Q1', 'reponse' => 'R1', 'valeur' => 2],
            ['question' => 'Q2', 'reponse' => 'R2', 'valeur' => 3]
        ]);
        $test->method('getScoreTotal')->willReturn(5);
        $test->method('getNombreQuestions')->willReturn(2);

        $this->groqAiService->method('isConfigured')->willReturn(true);
        $this->groqAiService->expects($this->once())
            ->method('genererAnalyse')
            ->with('stress', $this->anything(), 5, 2)
            ->willReturn('Analyse détaillée du test de stress');

        $result = $this->service->genererAnalyseFinale($test);

        $this->assertIsString($result);
        $this->assertEquals('Analyse détaillée du test de stress', $result);
    }

    public function testGenererAnalyseFinaleGroqError(): void
    {
        $test = $this->createMock(TestAdaptatif::class);
        $test->method('getCategorie')->willReturn('stress');
        $test->method('getQuestionsReponses')->willReturn([
            ['question' => 'Q1', 'reponse' => 'R1', 'valeur' => 2]
        ]);
        $test->method('getScoreTotal')->willReturn(2);
        $test->method('getNombreQuestions')->willReturn(1);

        $this->groqAiService->method('isConfigured')->willReturn(true);
        $this->groqAiService->expects($this->once())
            ->method('genererAnalyse')
            ->willThrowException(new \Exception('API Error'));

        $this->logger->expects($this->once())
            ->method('error');

        $result = $this->service->genererAnalyseFinale($test);

        $this->assertIsString($result); // Analyse par défaut générée
    }

    public function testExtraireProfilPatient(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getEmail')->willReturn('test@example.com');
        $user->method('getNom')->willReturn('Dupont');
        $user->method('getPrenom')->willReturn('Jean');
        $user->method('getDateNaissance')->willReturn(new \DateTime('1990-01-01'));

        $result = $this->service->extraireProfilPatient($user);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('email', $result);
        $this->assertArrayHasKey('nom', $result);
        $this->assertArrayHasKey('prenom', $result);
        $this->assertArrayHasKey('age', $result);
        $this->assertEquals('test@example.com', $result['email']);
        $this->assertEquals('Dupont', $result['nom']);
        $this->assertEquals('Jean', $result['prenom']);
        $this->assertGreaterThan(30, $result['age']); // Age calculé
    }

    public function testExtraireProfilPatientNullUser(): void
    {
        $result = $this->service->extraireProfilPatient(null);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    // Tests des méthodes privées via reflection
    public function testAnalyserTendanceCritique(): void
    {
        $reflection = new \ReflectionClass(QuestionnaireAdaptatifService::class);
        $method = $reflection->getMethod('analyserTendance');
        

        $questionsReponses = [
            ['valeur' => 3], ['valeur' => 3], ['valeur' => 3]
        ];

        $result = $method->invoke($this->service, $questionsReponses);
        $this->assertEquals('critique', $result);
    }

    public function testAnalyserTendanceStableFaible(): void
    {
        $reflection = new \ReflectionClass(QuestionnaireAdaptatifService::class);
        $method = $reflection->getMethod('analyserTendance');
        

        $questionsReponses = [
            ['valeur' => 1], ['valeur' => 1], ['valeur' => 1]
        ];

        $result = $method->invoke($this->service, $questionsReponses);
        $this->assertEquals('stable_faible', $result);
    }

    public function testAnalyserTendanceModere(): void
    {
        $reflection = new \ReflectionClass(QuestionnaireAdaptatifService::class);
        $method = $reflection->getMethod('analyserTendance');
        

        $questionsReponses = [
            ['valeur' => 2], ['valeur' => 2], ['valeur' => 1]
        ];

        $result = $method->invoke($this->service, $questionsReponses);
        $this->assertEquals('modere', $result);
    }

    public function testDoitArreterTestMaxQuestions(): void
    {
        $reflection = new \ReflectionClass(QuestionnaireAdaptatifService::class);
        $method = $reflection->getMethod('doitArreterTest');
        

        $test = $this->createMock(TestAdaptatif::class);
        $questionsReponses = array_fill(0, 10, ['question' => 'Q', 'reponse' => 'R', 'valeur' => 1]);
        $test->method('getQuestionsReponses')->willReturn($questionsReponses);
        $test->method('isTermine')->willReturn(false);

        $result = $method->invoke($this->service, $test);
        $this->assertTrue($result);
    }

    public function testDoitArreterTestDejaTermine(): void
    {
        $reflection = new \ReflectionClass(QuestionnaireAdaptatifService::class);
        $method = $reflection->getMethod('doitArreterTest');
        

        $test = $this->createMock(TestAdaptatif::class);
        $test->method('isTermine')->willReturn(true);

        $result = $method->invoke($this->service, $test);
        $this->assertTrue($result);
    }

    public function testDoitArreterTestScoreCritique(): void
    {
        $reflection = new \ReflectionClass(QuestionnaireAdaptatifService::class);
        $method = $reflection->getMethod('doitArreterTest');
        

        $test = $this->createMock(TestAdaptatif::class);
        $test->method('getQuestionsReponses')->willReturn([
            ['valeur' => 3], ['valeur' => 3], ['valeur' => 3], ['valeur' => 3], ['valeur' => 3]
        ]);
        $test->method('isTermine')->willReturn(false);

        $result = $method->invoke($this->service, $test);
        $this->assertTrue($result);
    }
}
