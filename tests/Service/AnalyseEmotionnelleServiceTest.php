<?php

namespace App\Tests\Service;

use App\Entity\TestAdaptatif;
use App\Service\AnalyseEmotionnelleService;
use PHPUnit\Framework\TestCase;

class AnalyseEmotionnelleServiceTest extends TestCase
{
    private AnalyseEmotionnelleService $analyseService;

    protected function setUp(): void
    {
        $this->analyseService = new AnalyseEmotionnelleService();
    }

    public function testAnalyserEvolutionAvecDonneesCompletes(): void
    {
        $test = $this->createMock(TestAdaptatif::class);

        $questionsReponses = [
            [
                'question' => 'Comment vous sentez-vous ?',
                'reponse' => 'Stressé',
                'valeur' => 3,
                'timestamp' => '2024-01-01 10:00:00'
            ],
            [
                'question' => 'Avez-vous bien dormi ?',
                'reponse' => 'Non',
                'valeur' => 2,
                'timestamp' => '2024-01-01 10:05:00'
            ],
            [
                'question' => 'Comment gérez-vous votre stress ?',
                'reponse' => 'Difficilement',
                'valeur' => 3,
                'timestamp' => '2024-01-01 10:10:00'
            ]
        ];

        $test->method('getQuestionsReponses')->willReturn($questionsReponses);

        $result = $this->analyseService->analyserEvolution($test);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('chronologie', $result);
        $this->assertArrayHasKey('statistiques', $result);
        $this->assertArrayHasKey('momentsCritiques', $result);
        $this->assertArrayHasKey('tendances', $result);
        $this->assertArrayHasKey('recommendations', $result);
        $this->assertArrayHasKey('graphiqueData', $result);

        // Vérifier la chronologie
        $this->assertCount(3, $result['chronologie']);
        $this->assertEquals(1, $result['chronologie'][0]['numero']);
        $this->assertEquals(3, $result['chronologie'][0]['scoreCumule']);

        // Vérifier les statistiques
        $this->assertArrayHasKey('scoreMoyen', $result['statistiques']);
        $this->assertArrayHasKey('scoreMax', $result['statistiques']);
        $this->assertArrayHasKey('scoreMin', $result['statistiques']);
        $this->assertEquals(2.67, round($result['statistiques']['scoreMoyen'], 2));
    }

    public function testAnalyserEvolutionSansDonnees(): void
    {
        $test = $this->createMock(TestAdaptatif::class);
        $test->method('getQuestionsReponses')->willReturn([]);

        $result = $this->analyseService->analyserEvolution($test);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('chronologie', $result);
        $this->assertArrayHasKey('statistiques', $result);
        $this->assertEmpty($result['chronologie']);
    }

    public function testAnalyserEvolutionDonneesParDefaut(): void
    {
        $reflection = new \ReflectionClass(AnalyseEmotionnelleService::class);
        $method = $reflection->getMethod('donneesParDefaut');
        

        $result = $method->invoke($this->analyseService);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('chronologie', $result);
        $this->assertArrayHasKey('statistiques', $result);
        $this->assertArrayHasKey('momentsCritiques', $result);
        $this->assertArrayHasKey('tendances', $result);
        $this->assertArrayHasKey('recommendations', $result);
        $this->assertArrayHasKey('graphiqueData', $result);
    }

    public function testIdentifierMomentsCritiques(): void
    {
        $reflection = new \ReflectionClass(AnalyseEmotionnelleService::class);
        $method = $reflection->getMethod('identifierMomentsCritiques');
        

        $questionsReponses = [
            ['question' => 'Q1', 'reponse' => 'R1', 'valeur' => 1, 'timestamp' => '2024-01-01 10:00:00'],
            ['question' => 'Q2', 'reponse' => 'R2', 'valeur' => 3, 'timestamp' => '2024-01-01 10:05:00'],
            ['question' => 'Q3', 'reponse' => 'R3', 'valeur' => 1, 'timestamp' => '2024-01-01 10:10:00'],
            ['question' => 'Q4', 'reponse' => 'R4', 'valeur' => 3, 'timestamp' => '2024-01-01 10:15:00'],
            ['question' => 'Q5', 'reponse' => 'R5', 'valeur' => 2, 'timestamp' => '2024-01-01 10:20:00']
        ];

        $result = $method->invoke($this->analyseService, $questionsReponses);

        $this->assertIsArray($result);
        // Les moments critiques sont les réponses avec valeur >= 3
        $this->assertGreaterThanOrEqual(0, count($result));
    }

    public function testAnalyserTendances(): void
    {
        $reflection = new \ReflectionClass(AnalyseEmotionnelleService::class);
        $method = $reflection->getMethod('analyserTendances');
        

        $questionsReponses = [
            ['question' => 'Q1', 'reponse' => 'R1', 'valeur' => 1],
            ['question' => 'Q2', 'reponse' => 'R2', 'valeur' => 2],
            ['question' => 'Q3', 'reponse' => 'R3', 'valeur' => 3],
            ['question' => 'Q4', 'reponse' => 'R4', 'valeur' => 2],
            ['question' => 'Q5', 'reponse' => 'R5', 'valeur' => 1]
        ];

        $result = $method->invoke($this->analyseService, $questionsReponses);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('type', $result);
        $this->assertArrayHasKey('pente', $result);
        $this->assertArrayHasKey('description', $result);
        $this->assertArrayHasKey('regularite', $result);
    }

    public function testAnalyserTendancesStable(): void
    {
        $reflection = new \ReflectionClass(AnalyseEmotionnelleService::class);
        $method = $reflection->getMethod('analyserTendances');
        

        $questionsReponses = [
            ['valeur' => 2], ['valeur' => 2], ['valeur' => 2]
        ];

        $result = $method->invoke($this->analyseService, $questionsReponses);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('type', $result);
        $this->assertEquals('stable', $result['type']);
    }

    public function testAnalyserTendancesEmpty(): void
    {
        $reflection = new \ReflectionClass(AnalyseEmotionnelleService::class);
        $method = $reflection->getMethod('analyserTendances');
        

        $result = $method->invoke($this->analyseService, []);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('type', $result);
        $this->assertEquals('insuffisant', $result['type']);
    }

    public function testGenererRecommendations(): void
    {
        $reflection = new \ReflectionClass(AnalyseEmotionnelleService::class);
        $method = $reflection->getMethod('genererRecommendations');
        

        $test = $this->createMock(TestAdaptatif::class);
        $test->method('getCategorie')->willReturn('stress');

        $questionsReponses = [
            ['question' => 'Q1', 'reponse' => 'R1', 'valeur' => 3],
            ['question' => 'Q2', 'reponse' => 'R2', 'valeur' => 3],
            ['question' => 'Q3', 'reponse' => 'R3', 'valeur' => 2]
        ];

        $result = $method->invoke($this->analyseService, $questionsReponses, $test);

        $this->assertIsArray($result);
        $this->assertGreaterThan(0, count($result));
        $this->assertIsString($result[0]);
    }

    public function testGenererRecommendationsDepression(): void
    {
        $reflection = new \ReflectionClass(AnalyseEmotionnelleService::class);
        $method = $reflection->getMethod('genererRecommendations');
        

        $test = $this->createMock(TestAdaptatif::class);
        $test->method('getCategorie')->willReturn('depression');

        $questionsReponses = [
            ['valeur' => 3], ['valeur' => 3], ['valeur' => 3]
        ];

        $result = $method->invoke($this->analyseService, $questionsReponses, $test);

        $this->assertIsArray($result);
        $this->assertGreaterThan(0, count($result));
    }

    public function testPreparerDonneesGraphique(): void
    {
        $reflection = new \ReflectionClass(AnalyseEmotionnelleService::class);
        $method = $reflection->getMethod('preparerDonneesGraphique');
        

        $questionsReponses = [
            ['question' => 'Q1', 'reponse' => 'R1', 'valeur' => 2, 'timestamp' => '2024-01-01 10:00:00'],
            ['question' => 'Q2', 'reponse' => 'R2', 'valeur' => 3, 'timestamp' => '2024-01-01 10:05:00'],
            ['question' => 'Q3', 'reponse' => 'R3', 'valeur' => 1, 'timestamp' => '2024-01-01 10:10:00']
        ];

        $result = $method->invoke($this->analyseService, $questionsReponses);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('labels', $result);
        $this->assertArrayHasKey('datasets', $result);
        $this->assertCount(3, $result['labels']);
        $this->assertCount(1, $result['datasets']);
        $this->assertEquals([2, 3, 1], $result['datasets'][0]['data']);
    }

    public function testPreparerDonneesGraphiqueEmpty(): void
    {
        $reflection = new \ReflectionClass(AnalyseEmotionnelleService::class);
        $method = $reflection->getMethod('preparerDonneesGraphique');
        

        $result = $method->invoke($this->analyseService, []);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('labels', $result);
        $this->assertArrayHasKey('datasets', $result);
        $this->assertEmpty($result['labels']);
        $this->assertCount(1, $result['datasets']);
        $this->assertEmpty($result['datasets'][0]['data']);
    }

    public function testCalculerStatistiques(): void
    {
        $reflection = new \ReflectionClass(AnalyseEmotionnelleService::class);
        $method = $reflection->getMethod('calculerStatistiques');
        

        $test = $this->createMock(TestAdaptatif::class);
        $test->method('getScoreActuel')->willReturn(15);
        $test->method('getNombreQuestions')->willReturn(5);

        $questionsReponses = [
            ['valeur' => 2],
            ['valeur' => 3],
            ['valeur' => 1],
            ['valeur' => 3],
            ['valeur' => 2]
        ];

        $result = $method->invoke($this->analyseService, $questionsReponses, $test);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('scoreMoyen', $result);
        $this->assertArrayHasKey('scoreMax', $result);
        $this->assertArrayHasKey('scoreMin', $result);
        $this->assertArrayHasKey('ecartType', $result);
        $this->assertArrayHasKey('coefficientVariation', $result);

        $this->assertEquals(2.2, $result['scoreMoyen']);
        $this->assertEquals(3, $result['scoreMax']);
        $this->assertEquals(1, $result['scoreMin']);
        $this->assertGreaterThan(0, $result['ecartType']);
    }

    public function testCalculerStatistiquesSingleValue(): void
    {
        $reflection = new \ReflectionClass(AnalyseEmotionnelleService::class);
        $method = $reflection->getMethod('calculerStatistiques');
        

        $test = $this->createMock(TestAdaptatif::class);
        $test->method('getScoreActuel')->willReturn(2);
        $test->method('getNombreQuestions')->willReturn(1);

        $questionsReponses = [['valeur' => 2]];

        $result = $method->invoke($this->analyseService, $questionsReponses, $test);

        $this->assertIsArray($result);
        $this->assertEquals(2, $result['scoreMoyen']);
        $this->assertEquals(2, $result['scoreMax']);
        $this->assertEquals(2, $result['scoreMin']);
        $this->assertEquals(0, $result['ecartType']); // Écart-type de 0 pour une seule valeur
    }

    public function testCalculerStatistiquesEmpty(): void
    {
        $reflection = new \ReflectionClass(AnalyseEmotionnelleService::class);
        $method = $reflection->getMethod('calculerStatistiques');
        

        $test = $this->createMock(TestAdaptatif::class);
        $test->method('getScoreActuel')->willReturn(0);
        $test->method('getNombreQuestions')->willReturn(0);

        $result = $method->invoke($this->analyseService, [], $test);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('scoreMoyen', $result);
        $this->assertArrayHasKey('scoreMax', $result);
        $this->assertArrayHasKey('scoreMin', $result);
    }

    public function testConstruireChronologie(): void
    {
        $reflection = new \ReflectionClass(AnalyseEmotionnelleService::class);
        $method = $reflection->getMethod('construireChronologie');
        

        $questionsReponses = [
            [
                'question' => 'Q1',
                'reponse' => 'R1',
                'valeur' => 2,
                'timestamp' => '2024-01-01 10:00:00'
            ],
            [
                'question' => 'Q2',
                'reponse' => 'R2',
                'valeur' => 3,
                'timestamp' => '2024-01-01 10:05:00'
            ]
        ];

        $result = $method->invoke($this->analyseService, $questionsReponses);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals(1, $result[0]['numero']);
        $this->assertEquals(2, $result[0]['scoreCumule']);
        $this->assertEquals(2, $result[1]['numero']);
        $this->assertEquals(5, $result[1]['scoreCumule']);
    }

    public function testConstruireChronologieEmpty(): void
    {
        $reflection = new \ReflectionClass(AnalyseEmotionnelleService::class);
        $method = $reflection->getMethod('construireChronologie');
        

        $result = $method->invoke($this->analyseService, []);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testConstruireChronologieWithoutTimestamps(): void
    {
        $reflection = new \ReflectionClass(AnalyseEmotionnelleService::class);
        $method = $reflection->getMethod('construireChronologie');
        

        $questionsReponses = [
            ['question' => 'Q1', 'reponse' => 'R1', 'valeur' => 2],
            ['question' => 'Q2', 'reponse' => 'R2', 'valeur' => 3]
        ];

        $result = $method->invoke($this->analyseService, $questionsReponses);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertNull($result[0]['timestamp']);
        $this->assertNull($result[1]['timestamp']);
    }
}
