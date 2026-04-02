<?php
namespace App\Service;

use App\Entity\TestAdaptatif;

class TestAdaptatifManager
{
    public function validate(TestAdaptatif $test): bool
    {
        $responses = $test->getQuestionsReponses();
        $count = count($responses);

        if ($count < 3) {
            throw new \InvalidArgumentException('Nombre de questions insuffisant');
        }
        if ($count > 10) {
            throw new \InvalidArgumentException('Nombre de questions excessif');
        }

        $sum = 0;
        foreach ($responses as $response) {
            if (!is_array($response) || !array_key_exists('valeur', $response)) {
                throw new \InvalidArgumentException('Valeur de réponse hors bornes');
            }
            $value = $response['valeur'];
            if (!is_int($value) || $value < 0 || $value > 3) {
                throw new \InvalidArgumentException('Valeur de réponse hors bornes');
            }
            $sum += $value;
        }

        $score = (int) $test->getScoreActuel();
        if ($sum !== $score) {
            throw new \InvalidArgumentException('Score incohérent');
        }

        $declaredCount = $test->getNombreQuestions();
        if ($declaredCount !== null && $declaredCount !== $count) {
            throw new \InvalidArgumentException('Nombre de questions incohérent');
        }

        return true;
    }
}
