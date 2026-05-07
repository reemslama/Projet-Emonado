<?php

namespace App\Tests\Service;

use App\Service\GroqAiService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class GroqAiServiceTest extends TestCase
{
    private GroqAiService $groqAiService;
    private $httpClient;
    private $logger;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->groqAiService = new GroqAiService(
            $this->httpClient,
            $this->logger,
            'test-api-key'
        );
    }

    public function testGenererQuestionPsychologiqueStress(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(200);
        $mockResponse->method('toArray')->willReturn([
            'choices' => [
                [
                    'message' => [
                        'content' => '{"question": "Comment gérez-vous votre stress quotidien ?", "reponses": [{"texte": "Bien", "valeur": 1}, {"texte": "Moyennement", "valeur": 2}, {"texte": "Difficilement", "valeur": 3}, {"texte": "Très mal", "valeur": 3}]}'
                    ]
                ]
            ]
        ]);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('POST', 'https://api.groq.com/openai/v1/chat/completions', $this->callback(function ($options) {
                $this->assertArrayHasKey('headers', $options);
                $this->assertArrayHasKey('json', $options);
                $this->assertEquals('Bearer test-api-key', $options['headers']['Authorization']);
                $this->assertStringContainsString('stress', $options['json']['messages'][0]['content']);
                return true;
            }))
            ->willReturn($mockResponse);

        $result = $this->groqAiService->genererQuestionPsychologique('stress');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('texte', $result);
        $this->assertArrayHasKey('reponses', $result);
        $this->assertEquals('Comment gérez-vous votre stress quotidien ?', $result['texte']);
        $this->assertCount(4, $result['reponses']);
    }

    public function testGenererQuestionPsychologiqueDepression(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(200);
        $mockResponse->method('toArray')->willReturn([
            'choices' => [
                [
                    'message' => [
                        'content' => '{"question": "Comment vous sentez-vous ces derniers jours ?", "reponses": [{"texte": "Bien", "valeur": 1}, {"texte": "Neutre", "valeur": 2}, {"texte": "Mal", "valeur": 3}, {"texte": "Très mal", "valeur": 3}]}'
                    ]
                ]
            ]
        ]);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with($this->anything(), $this->anything(), $this->anything())
            ->willReturn($mockResponse);

        $result = $this->groqAiService->genererQuestionPsychologique('depression');

        $this->assertIsArray($result);
        $this->assertEquals('Comment vous sentez-vous ces derniers jours ?', $result['texte']);
    }

    public function testGenererQuestionPsychologiqueIQ(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(200);
        $mockResponse->method('toArray')->willReturn([
            'choices' => [
                [
                    'message' => [
                        'content' => '{"question": "Quel nombre vient après 2, 4, 8, 16 ?", "reponses": [{"texte": "24", "valeur": 0}, {"texte": "32", "valeur": 1}, {"texte": "18", "valeur": 0}, {"texte": "64", "valeur": 0}]}'
                    ]
                ]
            ]
        ]);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->willReturn($mockResponse);

        $result = $this->groqAiService->genererQuestionPsychologique('iq');

        $this->assertIsArray($result);
        $this->assertStringContainsString('Quel nombre vient après', $result['texte']);
        $this->assertEquals(1, $result['reponses'][1]['valeur']); // Test logique: seule la réponse 32 (index 1) est correcte
    }

    public function testGenererQuestionPsychologiqueAvecHistorique(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(200);
        $mockResponse->method('toArray')->willReturn([
            'choices' => [
                [
                    'message' => [
                        'content' => '{"question": "Question adaptée au contexte", "reponses": [{"texte": "A", "valeur": 1}, {"texte": "B", "valeur": 2}, {"texte": "C", "valeur": 3}, {"texte": "D", "valeur": 3}]}'
                    ]
                ]
            ]
        ]);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->willReturn($mockResponse);

        $historique = [
            ['question' => 'Question précédente', 'reponse' => 'Réponse donnée', 'valeur' => 2]
        ];
        $profilPatient = ['age' => 30, 'genre' => 'F'];

        $result = $this->groqAiService->genererQuestionPsychologique('stress', $historique, $profilPatient, 'suivante');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('texte', $result);
    }

    public function testGenererQuestionPsychologiqueTypeApprofondissement(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(200);
        $mockResponse->method('toArray')->willReturn([
            'choices' => [
                [
                    'message' => [
                        'content' => '{"question": "Pouvez-vous développer votre réponse précédente ?", "reponses": [{"texte": "Un peu", "valeur": 1}, {"texte": "Modérément", "valeur": 2}, {"texte": "Beaucoup", "valeur": 3}, {"texte": "Énormément", "valeur": 3}]}'
                    ]
                ]
            ]
        ]);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->willReturn($mockResponse);

        $historique = [
            ['question' => 'Comment gérez-vous le stress ?', 'reponse' => 'Très difficilement', 'valeur' => 3]
        ];

        $result = $this->groqAiService->genererQuestionPsychologique('stress', $historique, [], 'approfondissement');

        $this->assertIsArray($result);
        $this->assertStringContainsString('développer', $result['texte']);
    }

    public function testGenererAnalyse(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(200);
        $mockResponse->method('toArray')->willReturn([
            'choices' => [
                [
                    'message' => [
                        'content' => 'Analyse détaillée du test de stress avec recommandations personnalisées.'
                    ]
                ]
            ]
        ]);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('POST', 'https://api.groq.com/openai/v1/chat/completions', $this->callback(function ($options) {
                $this->assertEquals(0.7, $options['json']['temperature']);
                $this->assertEquals(1000, $options['json']['max_tokens']);
                return true;
            }))
            ->willReturn($mockResponse);

        $questionsReponses = [
            ['question' => 'Q1', 'reponse' => 'R1', 'valeur' => 2],
            ['question' => 'Q2', 'reponse' => 'R2', 'valeur' => 3]
        ];

        $result = $this->groqAiService->genererAnalyse('stress', $questionsReponses, 5, 2);

        $this->assertIsString($result);
        $this->assertStringContainsString('Analyse', $result);
    }

    public function testGenererQuestionPsychologiqueApiError(): void
    {
        $this->httpClient->expects($this->once())
            ->method('request')
            ->willThrowException(new \Exception('API Error'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Erreur Groq API'));

        $result = $this->groqAiService->genererQuestionPsychologique('stress');

        // Devrait retourner une question par défaut
        $this->assertIsArray($result);
        $this->assertArrayHasKey('texte', $result);
        $this->assertArrayHasKey('reponses', $result);
        $this->assertCount(4, $result['reponses']);
    }

    public function testGenererAnalyseApiError(): void
    {
        $this->httpClient->expects($this->once())
            ->method('request')
            ->willThrowException(new \Exception('API Error'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Erreur génération analyse'));

        $questionsReponses = [['question' => 'Q1', 'reponse' => 'R1', 'valeur' => 2]];
        $result = $this->groqAiService->genererAnalyse('stress', $questionsReponses, 2, 1);

        // Devrait retourner une analyse par défaut
        $this->assertIsString($result);
        $this->assertStringContainsString('Analyse', $result);
    }

    public function testIsConfigured(): void
    {
        $result = $this->groqAiService->isConfigured();
        $this->assertTrue($result); // API key est définie dans setUp
    }

    public function testIsConfiguredWithoutApiKey(): void
    {
        $service = new GroqAiService(
            $this->httpClient,
            $this->logger,
            '' // API key vide
        );

        $result = $service->isConfigured();
        $this->assertFalse($result);
    }

    // Tests des méthodes privées via reflection
    public function testExtraireThemesAbordes(): void
    {
        $reflection = new \ReflectionClass(GroqAiService::class);
        $method = $reflection->getMethod('extraireThemesAbordes');
        

        $historique = [
            ['question' => 'Comment vous sentez-vous au travail ?', 'reponse' => 'Stressé', 'valeur' => 3],
            ['question' => 'Avez-vous des problèmes de sommeil ?', 'reponse' => 'Oui', 'valeur' => 2],
            ['question' => 'Comment vous sentez-vous au travail ?', 'reponse' => 'Anxieux', 'valeur' => 3]
        ];

        $result = $method->invoke($this->groqAiService, $historique, 'stress');

        $this->assertIsArray($result);
        $this->assertStringContainsStringIgnoringCase('travail', implode(' ', $result));
        $this->assertStringContainsStringIgnoringCase('sommeil', implode(' ', $result));
    }

    public function testExtraireThemesAbordesEmptyHistorique(): void
    {
        $reflection = new \ReflectionClass(GroqAiService::class);
        $method = $reflection->getMethod('extraireThemesAbordes');
        

        $result = $method->invoke($this->groqAiService, [], 'stress');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testConstruireSystemPromptStress(): void
    {
        $reflection = new \ReflectionClass(GroqAiService::class);
        $method = $reflection->getMethod('construireSystemPrompt');
        

        $result = $method->invoke($this->groqAiService, 'stress');

        $this->assertIsString($result);
        $this->assertStringContainsString('stress', $result);
        $this->assertStringContainsString('bienveillantes', $result);
        $this->assertStringContainsString('DIRECTIVES IMPORTANTES', $result);
    }

    public function testConstruireSystemPromptDepression(): void
    {
        $reflection = new \ReflectionClass(GroqAiService::class);
        $method = $reflection->getMethod('construireSystemPrompt');
        

        $result = $method->invoke($this->groqAiService, 'depression');

        $this->assertIsString($result);
        $this->assertStringContainsString('dépressifs', $result);
        $this->assertStringContainsString('sensibles', $result);
        $this->assertStringContainsString('non-jugeantes', $result);
    }

    public function testConstruireSystemPromptIQ(): void
    {
        $reflection = new \ReflectionClass(GroqAiService::class);
        $method = $reflection->getMethod('construireSystemPrompt');
        

        $result = $method->invoke($this->groqAiService, 'iq');

        $this->assertIsString($result);
        $this->assertStringContainsString('cognitives', $result);
        $this->assertStringContainsString('énigmes', $result);
        $this->assertStringContainsString('logique', $result);
    }

    public function testConstruireSystemPromptUnknownCategory(): void
    {
        $reflection = new \ReflectionClass(GroqAiService::class);
        $method = $reflection->getMethod('construireSystemPrompt');
        

        $result = $method->invoke($this->groqAiService, 'unknown');

        $this->assertIsString($result);
        $this->assertStringContainsString('général', $result);
    }

    public function testConstruireUserPrompt(): void
    {
        $reflection = new \ReflectionClass(GroqAiService::class);
        $method = $reflection->getMethod('construireUserPrompt');
        

        $historique = [
            ['question' => 'Question 1', 'reponse' => 'Réponse 1', 'valeur' => 2]
        ];
        $profilPatient = ['age' => 30, 'genre' => 'F'];

        $result = $method->invoke($this->groqAiService, 'stress', $historique, $profilPatient, 'suivante');

        $this->assertIsString($result);
        $this->assertStringContainsString('Question 1', $result);
        $this->assertStringContainsString('Réponse 1', $result);
        // Le prompt ne contient pas forcément les mots exacts
    }

    public function testConstruireUserPromptEmptyData(): void
    {
        $reflection = new \ReflectionClass(GroqAiService::class);
        $method = $reflection->getMethod('construireUserPrompt');
        

        $result = $method->invoke($this->groqAiService, 'stress', [], [], 'initial');

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testParseResponseValidJson(): void
    {
        $reflection = new \ReflectionClass(GroqAiService::class);
        $method = $reflection->getMethod('parseResponse');
        

        $response = [
            'choices' => [
                [
                    'message' => [
                        'content' => '{"question": "Test question", "reponses": [{"texte": "A", "valeur": 1}, {"texte": "B", "valeur": 2}, {"texte": "C", "valeur": 3}, {"texte": "D", "valeur": 4}]}'
                    ]
                ]
            ]
        ];

        $result = $method->invoke($this->groqAiService, $response);

        $this->assertIsArray($result);
        $this->assertEquals('Test question', $result['texte']);
        $this->assertCount(4, $result['reponses']);
    }

    public function testParseResponseInvalidJson(): void
    {
        $reflection = new \ReflectionClass(GroqAiService::class);
        $method = $reflection->getMethod('parseResponse');
        

        $response = [
            'choices' => [
                [
                    'message' => [
                        'content' => 'Invalid JSON content'
                    ]
                ]
            ]
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Erreur parsing JSON');

        $method->invoke($this->groqAiService, $response);
    }

    public function testGetQuestionParDefaut(): void
    {
        $reflection = new \ReflectionClass(GroqAiService::class);
        $method = $reflection->getMethod('getQuestionParDefaut');
        

        $result = $method->invoke($this->groqAiService, 'stress', 'initial');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('texte', $result);
        $this->assertArrayHasKey('reponses', $result);
        $this->assertCount(4, $result['reponses']);
    }

    public function testGetAnalyseParDefaut(): void
    {
        $reflection = new \ReflectionClass(GroqAiService::class);
        $method = $reflection->getMethod('getAnalyseParDefaut');
        

        $result = $method->invoke($this->groqAiService, 'stress', 5, 2);

        $this->assertIsString($result);
        $this->assertStringContainsString('Analyse', $result);
        $this->assertStringContainsString('stress', $result);
    }
}
