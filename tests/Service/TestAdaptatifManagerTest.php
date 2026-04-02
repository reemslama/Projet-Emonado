<?php
namespace App\Tests\Service;

use App\Entity\TestAdaptatif;
use App\Service\TestAdaptatifManager;
use PHPUnit\Framework\TestCase;

class TestAdaptatifManagerTest extends TestCase
{
    private function buildTest(array $values, int $score, int $nombre, bool $termine = false): TestAdaptatif
    {
        $reponses = [];
        foreach ($values as $i => $val) {
            $reponses[] = [
                'question' => 'Q'.($i + 1),
                'reponse'  => 'R'.($i + 1),
                'valeur'   => $val,
            ];
        }

        $test = new TestAdaptatif();
        $test->setQuestionsReponses($reponses)
             ->setScoreActuel($score)
             ->setNombreQuestions($nombre)
             ->setTermine($termine);

        return $test;
    }

    public function testValidTest(): void
    {
        $test = $this->buildTest([1, 2, 0], 3, 3, true);
        $manager = new TestAdaptatifManager();
        $this->assertTrue($manager->validate($test));
    }

    public function testScoreIncoherent(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Score incohérent');

        $test = $this->buildTest([1, 1, 1], 5, 3, false);
        (new TestAdaptatifManager())->validate($test);
    }

    public function testTropPeuDeQuestions(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Nombre de questions insuffisant');

        $test = $this->buildTest([1, 0], 1, 2, false);
        (new TestAdaptatifManager())->validate($test);
    }

    public function testTropDeQuestions(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Nombre de questions excessif');

        $values = array_fill(0, 11, 1);
        $test = $this->buildTest($values, 11, 11, false);
        (new TestAdaptatifManager())->validate($test);
    }

    public function testValeurHorsBornes(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Valeur de réponse hors bornes');

        $test = $this->buildTest([1, 5, 0], 6, 3, false);
        (new TestAdaptatifManager())->validate($test);
    }
}