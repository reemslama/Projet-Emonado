<?php

namespace App\Service;

use App\Entity\User;

/**
 * Service de prédiction d'évolution de l'état du patient pour le psychologue.
 * Combine une analyse statistique (tendance, régression linéaire) et une option IA (OpenAI).
 */
class PredictionEvolutionService
{
    private const SCORES = ['SOS' => 1, 'en colere' => 2, 'calme' => 3, 'heureux' => 4];
    private const SEUIL_TENDANCE = 0.02; // pente minimale pour considérer amélioration/détérioration
    private const HORIZON_JOURS = 14;    // prédiction sur 2 semaines

    public function __construct(
        private readonly ?string $openaiApiKey = null
    ) {
    }

    /**
     * Calcule la prédiction d'évolution pour un patient à partir de ses données.
     *
     * @param array<int, array{date: string, score: int, humeur: string}> $chartEvolution
     * @param array<string, int>                                         $statsHumeurs
     * @param array{moyenne_stress: float, moyenne_bien_etre: float, emotions_frequentes: array} $statsAnalyses
     * @return array{tendance: string, score_predicted: float|null, message: string, confiance: int, message_ia: string|null}
     */
    public function predict(
        array $chartEvolution,
        array $statsHumeurs,
        array $statsAnalyses,
        ?int $nbConsultations
    ): array {
        $result = [
            'tendance' => 'stable',
            'score_predicted' => null,
            'message' => '',
            'confiance' => 0,
            'message_ia' => null,
        ];

        if (\count($chartEvolution) < 3) {
            $result['message'] = 'Données insuffisantes pour une prédiction. Encouragez le patient à renseigner régulièrement son journal.';
            return $result;
        }

        $scores = array_column($chartEvolution, 'score');
        $n = \count($scores);
        $reg = $this->linearRegression($scores);

        $slope = $reg['slope'];
        $intercept = $reg['intercept'];
        $r2 = $reg['r2'];

        // Score prédit dans 2 semaines (on suppose ~1 point par semaine si données quotidiennes, sinon on extrapole)
        $pointsParSemaine = max(1, $n / max(1, $this->joursCouverts($chartEvolution) / 7));
        $xPred = $n + ($pointsParSemaine * 2); // +2 semaines
        $scorePred = max(1, min(4, round($intercept + $slope * $xPred, 1)));
        $result['score_predicted'] = $scorePred;

        // Tendance
        if ($slope > self::SEUIL_TENDANCE) {
            $result['tendance'] = 'amelioration';
        } elseif ($slope < -self::SEUIL_TENDANCE) {
            $result['tendance'] = 'deterioration';
        }

        // Confiance (basée sur R² et nombre de points)
        $confiance = (int) min(95, 30 + (int) ($r2 * 40) + min(25, $n));
        $result['confiance'] = $confiance;

        // Message explicatif
        $labels = [1 => 'SOS', 2 => 'en colère', 3 => 'calme', 4 => 'heureux'];
        $labelPred = $labels[(int) round($scorePred)] ?? '—';
        $moyenneActuelle = array_sum($scores) / $n;

        if ($result['tendance'] === 'amelioration') {
            $result['message'] = sprintf(
                'Tendance à l\'amélioration. Score actuel moyen : %.1f. Prédiction sous 2 semaines : %.1f (%s). Le patient montre une évolution positive dans son journal.',
                $moyenneActuelle,
                $scorePred,
                $labelPred
            );
        } elseif ($result['tendance'] === 'deterioration') {
            $result['message'] = sprintf(
                'Tendance à la détérioration. Score actuel moyen : %.1f. Prédiction sous 2 semaines : %.1f (%s). Une attention particulière est recommandée.',
                $moyenneActuelle,
                $scorePred,
                $labelPred
            );
        } else {
            $result['message'] = sprintf(
                'Évolution stable. Score moyen : %.1f. Prédiction maintenue autour de %.1f (%s) pour les 2 prochaines semaines.',
                $moyenneActuelle,
                $scorePred,
                $labelPred
            );
        }

        // Appel IA optionnel (OpenAI)
        if ($this->openaiApiKey && $this->openaiApiKey !== '') {
            $result['message_ia'] = $this->generateAISummary(
                $chartEvolution,
                $statsHumeurs,
                $statsAnalyses,
                $nbConsultations ?? 0,
                $result
            );
        }

        return $result;
    }

    private function linearRegression(array $y): array
    {
        $n = \count($y);
        $x = range(0, $n - 1);
        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumXY = 0;
        $sumX2 = 0;
        for ($i = 0; $i < $n; $i++) {
            $sumXY += $x[$i] * $y[$i];
            $sumX2 += $x[$i] * $x[$i];
        }
        $denom = $n * $sumX2 - $sumX * $sumX;
        $slope = $denom != 0 ? ($n * $sumXY - $sumX * $sumY) / $denom : 0;
        $intercept = ($sumY - $slope * $sumX) / $n;

        $meanY = $sumY / $n;
        $ssTot = 0;
        $ssRes = 0;
        for ($i = 0; $i < $n; $i++) {
            $pred = $intercept + $slope * $x[$i];
            $ssTot += ($y[$i] - $meanY) ** 2;
            $ssRes += ($y[$i] - $pred) ** 2;
        }
        $r2 = $ssTot > 0 ? max(0, 1 - $ssRes / $ssTot) : 0;

        return ['slope' => $slope, 'intercept' => $intercept, 'r2' => $r2];
    }

    private function joursCouverts(array $chartEvolution): int
    {
        if (\count($chartEvolution) < 2) {
            return 1;
        }
        $first = \DateTime::createFromFormat('Y-m-d', $chartEvolution[0]['date']);
        $last = \DateTime::createFromFormat('Y-m-d', $chartEvolution[\count($chartEvolution) - 1]['date']);
        if (!$first || !$last) {
            return 30;
        }
        return max(1, (int) $last->diff($first)->days);
    }

    private function generateAISummary(
        array $chartEvolution,
        array $statsHumeurs,
        array $statsAnalyses,
        int $nbConsultations,
        array $predictionResult
    ): ?string {
        if (!$this->openaiApiKey) {
            return null;
        }

        $scores = array_column($chartEvolution, 'score');
        $moyenne = \count($scores) > 0 ? round(array_sum($scores) / \count($scores), 1) : 0;
        $texteTendance = match ($predictionResult['tendance']) {
            'amelioration' => 'amélioration',
            'deterioration' => 'détérioration',
            default => 'stabilité',
        };

        $prompt = sprintf(
            "Tu es un assistant pour psychologues. Voici des données anonymisées d'un patient:\n" .
            "- Entrées journal (humeurs): %d (SOS: %d, en colère: %d, calme: %d, heureux: %d)\n" .
            "- Score moyen humeur (1-4): %.1f\n" .
            "- Stress moyen: %.1f, Bien-être moyen: %.1f\n" .
            "- Nombre de consultations: %d\n" .
            "- Tendance calculée: %s, score prédit 2 sem: %.1f, confiance: %d%%\n\n" .
            "Écris UNE SEULE phrase courte (max 80 mots) de synthèse pour le psychologue: prédiction d'évolution et recommandation pratique. Pas de formules, ton professionnel.",
            array_sum($statsHumeurs),
            $statsHumeurs['SOS'] ?? 0,
            $statsHumeurs['en colere'] ?? 0,
            $statsHumeurs['calme'] ?? 0,
            $statsHumeurs['heureux'] ?? 0,
            $moyenne,
            $statsAnalyses['moyenne_stress'] ?? 0,
            $statsAnalyses['moyenne_bien_etre'] ?? 0,
            $nbConsultations,
            $texteTendance,
            $predictionResult['score_predicted'] ?? 0,
            $predictionResult['confiance'] ?? 0
        );

        try {
            $ch = curl_init('https://api.openai.com/v1/chat/completions');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->openaiApiKey,
                ],
                CURLOPT_POSTFIELDS => json_encode([
                    'model' => 'gpt-4o-mini',
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                    'max_tokens' => 150,
                    'temperature' => 0.5,
                ]),
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $response) {
                $data = json_decode($response, true);
                $text = $data['choices'][0]['message']['content'] ?? null;
                return $text ? trim($text) : null;
            }
        } catch (\Throwable) {
            // Ignore errors
        }

        return null;
    }
}
