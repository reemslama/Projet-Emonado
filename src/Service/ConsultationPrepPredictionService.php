<?php

namespace App\Service;

use App\Entity\ConsultationQuestionnaire;

/**
 * Heuristique locale pour synthétiser un score clinique et un libellé d'état à partir du formulaire pré-consultation.
 */
final class ConsultationPrepPredictionService
{
    /**
     * @return array{clinical_score: int, short_label: string, summary_html_safe: string, severity_rank: int, chart_labels: list<string>, chart_values: list<int>, chart_colors: list<string>}
     */
    public function analyze(?ConsultationQuestionnaire $q): array
    {
        $defaults = [
            'clinical_score' => 0,
            'short_label' => '—',
            'summary_html_safe' => 'Le patient n’a pas encore envoyé de formulaire pré-consultation.',
            'severity_rank' => 0,
            'chart_labels' => ['Stress', 'Anxiété', 'Humeur', 'Sommeil', 'Énergie', 'Urgence'],
            'chart_values' => [0, 0, 0, 0, 0, 0],
            'chart_colors' => ['#94a3b8', '#94a3b8', '#94a3b8', '#94a3b8', '#94a3b8', '#94a3b8'],
        ];

        if (!$q instanceof ConsultationQuestionnaire) {
            return $defaults;
        }

        $stress = $q->getStress();
        $anx = $q->getAnxiete();
        $mood = $q->getHumeur();
        $sleep = $q->getSommeil();
        $energy = $q->getEnergie();
        $support = $q->getSoutien();
        $urgency = $q->getUrgenceRessentie();

        $harmRank = match ($q->getRisqueAutoAgression()) {
            'high' => 100,
            'moderate' => 55,
            'low' => 25,
            default => 0,
        };

        // Déstress positif sur humeur/sommeil/énergie/soutien (inverse si score faible = état dégradé)
        $negativeImpact =
            $stress + $anx + $urgency
            + (10 - $mood)
            + (10 - $sleep)
            + (10 - $energy)
            + (10 - $support);

        $clinical = (int) round(min(100, ($negativeImpact * 2.2) + $harmRank));

        $severityRank = $clinical + ($harmRank > 0 ? 40 : 0);

        $chartLabels = ['Stress', 'Anxiété', 'Humeur', 'Sommeil', 'Énergie', 'Urgence'];
        $chartValues = [$stress, $anx, $mood, $sleep, $energy, $urgency];

        $chartColors = array_map(static function (int $v): string {
            if ($v <= 3) {
                return '#22c55e';
            }
            if ($v <= 6) {
                return '#f59e0b';
            }

            return '#ef4444';
        }, $chartValues);

        [$shortLabel, $summary] = $this->wording($clinical, $q);

        return [
            'clinical_score' => $clinical,
            'short_label' => $shortLabel,
            'summary_html_safe' => $summary,
            'severity_rank' => $severityRank,
            'chart_labels' => $chartLabels,
            'chart_values' => $chartValues,
            'chart_colors' => $chartColors,
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function wording(int $clinical, ConsultationQuestionnaire $q): array
    {
        $harm = $q->getRisqueAutoAgression();

        if (in_array($harm, ['high', 'moderate'], true)) {
            return [
                'Risque élevé signalé',
                '<strong class="text-danger">Situation à prioriser :</strong> risque auto-agression signalé dans le formulaire. '
                . 'Prévoir une évaluation rapide et les protocoles habituels de sécurisation.',
            ];
        }

        if ($clinical >= 72) {
            return [
                'Détresse importante',
                '<strong class="text-danger">État préoccupant :</strong> plusieurs indicateurs suggèrent une détresse importante. '
                . 'Prévoir un temps d’écoute renforcé et une évaluation approfondie en séance.',
            ];
        }

        if ($clinical >= 48) {
            return [
                'Détresse modérée',
                '<strong class="text-warning">État modéré :</strong> plaintes significatives avec besoin de structuration du suivi. '
                . 'Explorer les facteurs de maintien et les ressources du patient.',
            ];
        }

        return [
            'État globalement stable',
            '<strong class="text-success">État non grave :</strong> suivi standard possible avec consultation classique.',
        ];
    }
}
