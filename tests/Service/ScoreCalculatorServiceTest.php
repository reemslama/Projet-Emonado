<?php

namespace App\Tests\Service;

use App\Entity\Reponse;
use App\Service\ScoreCalculatorService;
use PHPUnit\Framework\TestCase;

class ScoreCalculatorServiceTest extends TestCase
{
    private ScoreCalculatorService $scoreCalculator;

    protected function setUp(): void
    {
        $this->scoreCalculator = new ScoreCalculatorService();
    }

    public function testCalculateTotalScore(): void
    {
        $reponse1 = $this->createMock(Reponse::class);
        $reponse1->method('getValeur')->willReturn(2);

        $reponse2 = $this->createMock(Reponse::class);
        $reponse2->method('getValeur')->willReturn(3);

        $reponse3 = $this->createMock(Reponse::class);
        $reponse3->method('getValeur')->willReturn(1);

        $reponses = [$reponse1, $reponse2, $reponse3];

        $result = $this->scoreCalculator->calculateTotalScore($reponses);

        $this->assertEquals(6, $result);
    }

    public function testCalculateTotalScoreEmptyArray(): void
    {
        $result = $this->scoreCalculator->calculateTotalScore([]);

        $this->assertEquals(0, $result);
    }

    public function testCalculateTotalScoreSingleResponse(): void
    {
        $reponse = $this->createMock(Reponse::class);
        $reponse->method('getValeur')->willReturn(5);

        $result = $this->scoreCalculator->calculateTotalScore([$reponse]);

        $this->assertEquals(5, $result);
    }

    public function testInterpretScoreMoralExcellent(): void
    {
        $result = $this->scoreCalculator->interpretScore(2, 'moral');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('niveau', $result);
        $this->assertArrayHasKey('couleur', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertEquals('Excellent', $result['niveau']);
        $this->assertEquals('success', $result['couleur']);
        $this->assertStringContainsStringIgnoringCase('excellent', $result['message']);
    }

    public function testInterpretScoreMoralBon(): void
    {
        $result = $this->scoreCalculator->interpretScore(6, 'moral');

        $this->assertEquals('Bon', $result['niveau']);
        $this->assertEquals('info', $result['couleur']);
        $this->assertStringContainsStringIgnoringCase('bon', $result['message']);
    }

    public function testInterpretScoreMoralMoyen(): void
    {
        $result = $this->scoreCalculator->interpretScore(10, 'moral');

        $this->assertEquals('Moyen', $result['niveau']);
        $this->assertEquals('warning', $result['couleur']);
        $this->assertStringContainsStringIgnoringCase('moyen', $result['message']);
    }

    public function testInterpretScoreMoralPreoccupant(): void
    {
        $result = $this->scoreCalculator->interpretScore(14, 'moral');

        $this->assertEquals('Préoccupant', $result['niveau']);
        $this->assertEquals('danger', $result['couleur']);
        $this->assertStringContainsStringIgnoringCase('nécessite attention', $result['message']);
    }

    public function testInterpretScoreMoralCritique(): void
    {
        $result = $this->scoreCalculator->interpretScore(20, 'moral');

        $this->assertEquals('Critique', $result['niveau']);
        $this->assertEquals('danger', $result['couleur']);
        $this->assertStringContainsStringIgnoringCase('consulter un professionnel', $result['message']);
    }

    public function testInterpretScoreStressFaible(): void
    {
        $result = $this->scoreCalculator->interpretScore(2, 'stress');

        $this->assertEquals('Faible', $result['niveau']);
        $this->assertEquals('success', $result['couleur']);
        $this->assertStringContainsStringIgnoringCase('faible', $result['message']);
    }

    public function testInterpretScoreStressModere(): void
    {
        $result = $this->scoreCalculator->interpretScore(5, 'stress');

        $this->assertEquals('Modéré', $result['niveau']);
        $this->assertEquals('warning', $result['couleur']);
        $this->assertStringContainsStringIgnoringCase('modéré', $result['message']);
    }

    public function testInterpretScoreStressEleve(): void
    {
        $result = $this->scoreCalculator->interpretScore(10, 'stress');

        $this->assertEquals('Élevé', $result['niveau']);
        $this->assertEquals('danger', $result['couleur']);
        $this->assertStringContainsStringIgnoringCase('élevé', $result['message']);
    }

    public function testInterpretScoreDepressionFaible(): void
    {
        $result = $this->scoreCalculator->interpretScore(2, 'depression');

        // Devrait utiliser le barème par défaut (moral) si catégorie inconnue
        $this->assertEquals('Excellent', $result['niveau']);
    }

    public function testInterpretScoreIQFaible(): void
    {
        $result = $this->scoreCalculator->interpretScore(2, 'iq');

        // Devrait utiliser le barème par défaut (moral) si catégorie inconnue
        $this->assertEquals('Excellent', $result['niveau']);
    }

    public function testInterpretScoreScoreNegatif(): void
    {
        $result = $this->scoreCalculator->interpretScore(-1, 'moral');

        $this->assertEquals('Indéterminé', $result['niveau']);
    }

    public function testInterpretScoreScoreTresEleve(): void
    {
        $result = $this->scoreCalculator->interpretScore(50, 'moral');

        $this->assertEquals('Critique', $result['niveau']);
    }

    public function testCalculateScoreByCategory(): void
    {
        $question1 = $this->createMock(\App\Entity\Question::class);
        $question1->method('getId')->willReturn(1);
        $question1->method('getCategorie')->willReturn('stress');

        $question2 = $this->createMock(\App\Entity\Question::class);
        $question2->method('getId')->willReturn(2);
        $question2->method('getCategorie')->willReturn('stress');

        $question3 = $this->createMock(\App\Entity\Question::class);
        $question3->method('getId')->willReturn(3);
        $question3->method('getCategorie')->willReturn('depression');

        $question4 = $this->createMock(\App\Entity\Question::class);
        $question4->method('getId')->willReturn(4);
        $question4->method('getCategorie')->willReturn('iq');

        $reponse1 = $this->createMock(\App\Entity\Reponse::class);
        $reponse1->method('getValeur')->willReturn(2);
        $reponse1->method('getQuestion')->willReturn($question1);

        $reponse2 = $this->createMock(\App\Entity\Reponse::class);
        $reponse2->method('getValeur')->willReturn(3);
        $reponse2->method('getQuestion')->willReturn($question2);

        $reponse3 = $this->createMock(\App\Entity\Reponse::class);
        $reponse3->method('getValeur')->willReturn(1);
        $reponse3->method('getQuestion')->willReturn($question3);

        $reponse4 = $this->createMock(\App\Entity\Reponse::class);
        $reponse4->method('getValeur')->willReturn(2);
        $reponse4->method('getQuestion')->willReturn($question4);

        $questions = [$question1, $question2, $question3, $question4];
        $reponses = [$reponse1, $reponse2, $reponse3, $reponse4];

        $result = $this->scoreCalculator->calculateScoreByCategory($questions, $reponses);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('stress', $result);
        $this->assertArrayHasKey('depression', $result);
        $this->assertArrayHasKey('iq', $result);

        $this->assertEquals(5, $result['stress']); // 2 + 3
        $this->assertEquals(1, $result['depression']); // 1
        $this->assertEquals(2, $result['iq']); // 2
    }

    public function testCalculateScoreByCategoryEmptyData(): void
    {
        $result = $this->scoreCalculator->calculateScoreByCategory([], []);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testCalculateScoreByCategorySingleCategory(): void
    {
        $question1 = $this->createMock(\App\Entity\Question::class);
        $question1->method('getId')->willReturn(1);
        $question1->method('getCategorie')->willReturn('stress');

        $question2 = $this->createMock(\App\Entity\Question::class);
        $question2->method('getId')->willReturn(2);
        $question2->method('getCategorie')->willReturn('stress');

        $reponse1 = $this->createMock(\App\Entity\Reponse::class);
        $reponse1->method('getValeur')->willReturn(1);
        $reponse1->method('getQuestion')->willReturn($question1);

        $reponse2 = $this->createMock(\App\Entity\Reponse::class);
        $reponse2->method('getValeur')->willReturn(1);
        $reponse2->method('getQuestion')->willReturn($question2);

        $questions = [$question1, $question2];
        $reponses = [$reponse1, $reponse2];

        $result = $this->scoreCalculator->calculateScoreByCategory($questions, $reponses);

        $this->assertCount(1, $result);
        $this->assertArrayHasKey('stress', $result);
        $this->assertEquals(2, $result['stress']); // 1 + 1
    }

    public function testCalculateScoreByCategoryMissingResponses(): void
    {
        $question1 = $this->createMock(\App\Entity\Question::class);
        $question1->method('getId')->willReturn(1);
        $question1->method('getCategorie')->willReturn('stress');

        $question2 = $this->createMock(\App\Entity\Question::class);
        $question2->method('getId')->willReturn(2);
        $question2->method('getCategorie')->willReturn('stress');

        $reponse1 = $this->createMock(\App\Entity\Reponse::class);
        $reponse1->method('getValeur')->willReturn(2);
        $reponse1->method('getQuestion')->willReturn($question1);

        $questions = [$question1, $question2];
        $reponses = [$reponse1]; // Une seule réponse pour 2 questions

        $result = $this->scoreCalculator->calculateScoreByCategory($questions, $reponses);

        $this->assertArrayHasKey('stress', $result);
        $this->assertEquals(2, $result['stress']); // Seulement la première réponse
    }

    public function testCalculateScoreByCategoryInvalidQuestionId(): void
    {
        $question1 = $this->createMock(\App\Entity\Question::class);
        $question1->method('getId')->willReturn(1);
        $question1->method('getCategorie')->willReturn('stress');

        $invalidQuestion = $this->createMock(\App\Entity\Question::class);
        $invalidQuestion->method('getId')->willReturn(999);

        $reponse1 = $this->createMock(\App\Entity\Reponse::class);
        $reponse1->method('getValeur')->willReturn(2);
        $reponse1->method('getQuestion')->willReturn($invalidQuestion); // Question invalide

        $questions = [$question1];
        $reponses = [$reponse1];

        $result = $this->scoreCalculator->calculateScoreByCategory($questions, $reponses);

        $this->assertArrayHasKey('stress', $result);
        $this->assertEquals(0, $result['stress']); // Aucune réponse ne correspond
    }

    public function testCalculateScoreByCategoryZeroValues(): void
    {
        $question1 = $this->createMock(\App\Entity\Question::class);
        $question1->method('getId')->willReturn(1);
        $question1->method('getCategorie')->willReturn('stress');

        $question2 = $this->createMock(\App\Entity\Question::class);
        $question2->method('getId')->willReturn(2);
        $question2->method('getCategorie')->willReturn('stress');

        $reponse1 = $this->createMock(\App\Entity\Reponse::class);
        $reponse1->method('getValeur')->willReturn(0);
        $reponse1->method('getQuestion')->willReturn($question1);

        $reponse2 = $this->createMock(\App\Entity\Reponse::class);
        $reponse2->method('getValeur')->willReturn(0);
        $reponse2->method('getQuestion')->willReturn($question2);

        $questions = [$question1, $question2];
        $reponses = [$reponse1, $reponse2];

        $result = $this->scoreCalculator->calculateScoreByCategory($questions, $reponses);

        $this->assertEquals(0, $result['stress']); // 0 + 0
    }

    public function testCalculateScoreByCategoryNegativeValues(): void
    {
        $question1 = $this->createMock(\App\Entity\Question::class);
        $question1->method('getId')->willReturn(1);
        $question1->method('getCategorie')->willReturn('stress');

        $reponse1 = $this->createMock(\App\Entity\Reponse::class);
        $reponse1->method('getValeur')->willReturn(-1);
        $reponse1->method('getQuestion')->willReturn($question1);

        $questions = [$question1];
        $reponses = [$reponse1];

        $result = $this->scoreCalculator->calculateScoreByCategory($questions, $reponses);

        $this->assertEquals(-1, $result['stress']); // Valeur négative autorisée
    }
}