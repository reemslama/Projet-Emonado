<?php

namespace App\Service;

use App\Entity\AnalyseEmotionnelle;

class EmotionInsightService
{
    /**
     * @param AnalyseEmotionnelle[] $analyses Most recent first
     *
     * @return array{
     *   dominantEmotion:string,
     *   trajectory:string,
     *   summary:string,
     *   recommendations:string[],
     *   metrics:array{avgStress:float,avgWellBeing:float,sampleSize:int,sosCount:int}
     * }
     */
    public function buildInsight(array $analyses): array
    {
        $sampleSize = count($analyses);
        if ($sampleSize === 0) {
            return [
                'dominantEmotion' => 'indeterminee',
                'trajectory' => 'insuffisant',
                'summary' => 'Pas assez de donnees pour construire un parcours emotionnel.',
                'recommendations' => [
                    'Ajouter au moins 3 journaux pour declencher une analyse de tendance.',
                ],
                'metrics' => [
                    'avgStress' => 0.0,
                    'avgWellBeing' => 0.0,
                    'sampleSize' => 0,
                    'sosCount' => 0,
                ],
            ];
        }

        $emotionCounts = [];
        $stressTotal = 0.0;
        $wellBeingTotal = 0.0;
        $sosCount = 0;

        foreach ($analyses as $a) {
            $emotion = (string) ($a->getEmotionPrincipale() ?? 'neutre');
            $emotionCounts[$emotion] = ($emotionCounts[$emotion] ?? 0) + 1;
            $stressTotal += (float) ($a->getNiveauStress() ?? 0);
            $wellBeingTotal += (float) ($a->getScoreBienEtre() ?? 0);
            if ($a->getJournal()?->getHumeur() === 'SOS') {
                $sosCount++;
            }
        }

        arsort($emotionCounts);
        $dominantEmotion = (string) array_key_first($emotionCounts);

        $avgStress = round($stressTotal / $sampleSize, 2);
        $avgWellBeing = round($wellBeingTotal / $sampleSize, 2);

        $recentChunk = array_slice($analyses, 0, min(3, $sampleSize));
        $olderChunk = array_slice($analyses, min(3, $sampleSize));

        $recentStress = $this->avgStress($recentChunk);
        $recentWellBeing = $this->avgWellBeing($recentChunk);
        $olderStress = $this->avgStress($olderChunk);
        $olderWellBeing = $this->avgWellBeing($olderChunk);

        $trajectory = 'stable';
        if ($olderChunk !== []) {
            $stressDelta = $recentStress - $olderStress;
            $wellBeingDelta = $recentWellBeing - $olderWellBeing;

            if ($stressDelta >= 1.0 || $wellBeingDelta <= -8.0) {
                $trajectory = 'degradation';
            } elseif ($stressDelta <= -1.0 || $wellBeingDelta >= 8.0) {
                $trajectory = 'amelioration';
            }
        }

        $summary = sprintf(
            'Emotion dominante: %s. Trajectoire: %s. Stress moyen: %.2f/10, bien-etre moyen: %.2f/100 sur %d analyses.',
            $dominantEmotion,
            $trajectory,
            $avgStress,
            $avgWellBeing,
            $sampleSize
        );

        $recommendations = $this->recommendations($trajectory, $dominantEmotion, $avgStress, $avgWellBeing, $sosCount);

        return [
            'dominantEmotion' => $dominantEmotion,
            'trajectory' => $trajectory,
            'summary' => $summary,
            'recommendations' => $recommendations,
            'metrics' => [
                'avgStress' => $avgStress,
                'avgWellBeing' => $avgWellBeing,
                'sampleSize' => $sampleSize,
                'sosCount' => $sosCount,
            ],
        ];
    }

    /** @param AnalyseEmotionnelle[] $analyses */
    private function avgStress(array $analyses): float
    {
        if ($analyses === []) {
            return 0.0;
        }
        $sum = 0.0;
        foreach ($analyses as $a) {
            $sum += (float) ($a->getNiveauStress() ?? 0);
        }

        return $sum / count($analyses);
    }

    /** @param AnalyseEmotionnelle[] $analyses */
    private function avgWellBeing(array $analyses): float
    {
        if ($analyses === []) {
            return 0.0;
        }
        $sum = 0.0;
        foreach ($analyses as $a) {
            $sum += (float) ($a->getScoreBienEtre() ?? 0);
        }

        return $sum / count($analyses);
    }

    /** @return string[] */
    private function recommendations(
        string $trajectory,
        string $dominantEmotion,
        float $avgStress,
        float $avgWellBeing,
        int $sosCount
    ): array {
        $items = [];

        if ($trajectory === 'degradation') {
            $items[] = 'Prevoir un suivi rapproche cette semaine.';
        } elseif ($trajectory === 'amelioration') {
            $items[] = 'Consolider les routines qui ont bien fonctionne.';
        } else {
            $items[] = 'Maintenir un suivi regulier pour valider la stabilite.';
        }

        if ($avgStress >= 6.0) {
            $items[] = 'Introduire un exercice court de respiration 2 fois par jour.';
        }

        if ($avgWellBeing <= 45.0) {
            $items[] = 'Fixer un micro-objectif positif quotidien et le noter dans le journal.';
        }

        if ($sosCount >= 2) {
            $items[] = 'Verifier les facteurs declencheurs recurrents des episodes SOS.';
        }

        if ($dominantEmotion === 'colere') {
            $items[] = 'Proposer une technique de desescalade emotionnelle avant situation a risque.';
        }

        if ($dominantEmotion === 'tristesse') {
            $items[] = 'Renforcer le plan d activites sociales et de soutien.';
        }

        return array_values(array_unique($items));
    }
}
