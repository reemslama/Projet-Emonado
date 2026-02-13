<?php

namespace App\Service;

class ScoreCalculatorService
{
    /**
     * Calcule le score total à partir des réponses
     * 
     * @param array $reponses Tableau des réponses sélectionnées
     * @return int Score total
     */
    public function calculateTotalScore(array $reponses): int
    {
        $totalScore = 0;
        
        foreach ($reponses as $reponse) {
            if ($reponse instanceof \App\Entity\Reponse) {
                $totalScore += $reponse->getValeur();
            }
        }
        
        return $totalScore;
    }

    /**
     * Interprète le score selon un barème
     * 
     * @param int $score
     * @param string $categorie
     * @return array
     */
    public function interpretScore(int $score, string $categorie = 'moral'): array
    {
        $interpretations = [
            'moral' => [
                ['min' => 0, 'max' => 4, 'niveau' => 'Excellent', 'couleur' => 'success', 'message' => 'Votre état moral est excellent.'],
                ['min' => 5, 'max' => 8, 'niveau' => 'Bon', 'couleur' => 'info', 'message' => 'Votre état moral est bon.'],
                ['min' => 9, 'max' => 12, 'niveau' => 'Moyen', 'couleur' => 'warning', 'message' => 'Votre état moral est moyen. Prenez soin de vous.'],
                ['min' => 13, 'max' => 16, 'niveau' => 'Préoccupant', 'couleur' => 'danger', 'message' => 'Votre état moral nécessite attention.'],
                ['min' => 17, 'max' => 100, 'niveau' => 'Critique', 'couleur' => 'danger', 'message' => 'Nous vous conseillons de consulter un professionnel.'],
            ],
            'stress' => [
                ['min' => 0, 'max' => 3, 'niveau' => 'Faible', 'couleur' => 'success', 'message' => 'Votre niveau de stress est faible.'],
                ['min' => 4, 'max' => 7, 'niveau' => 'Modéré', 'couleur' => 'warning', 'message' => 'Votre niveau de stress est modéré.'],
                ['min' => 8, 'max' => 100, 'niveau' => 'Élevé', 'couleur' => 'danger', 'message' => 'Votre niveau de stress est élevé.'],
            ],
        ];

        $bareme = $interpretations[$categorie] ?? $interpretations['moral'];

        foreach ($bareme as $niveau) {
            if ($score >= $niveau['min'] && $score <= $niveau['max']) {
                return [
                    'score' => $score,
                    'niveau' => $niveau['niveau'],
                    'couleur' => $niveau['couleur'],
                    'message' => $niveau['message'],
                ];
            }
        }

        return [
            'score' => $score,
            'niveau' => 'Indéterminé',
            'couleur' => 'secondary',
            'message' => 'Score non interprétable.',
        ];
    }

    /**
     * Calcule le score par catégorie
     * 
     * @param array $questions
     * @param array $reponses
     * @return array
     */
    public function calculateScoreByCategory(array $questions, array $reponses): array
    {
        $scoresByCategory = [];

        foreach ($questions as $question) {
            $categorie = $question->getCategorie() ?? 'autre';
            
            if (!isset($scoresByCategory[$categorie])) {
                $scoresByCategory[$categorie] = 0;
            }

            // Trouver la réponse correspondante
            foreach ($reponses as $reponse) {
                if ($reponse->getQuestion()->getId() === $question->getId()) {
                    $scoresByCategory[$categorie] += $reponse->getValeur();
                }
            }
        }

        return $scoresByCategory;
    }
}