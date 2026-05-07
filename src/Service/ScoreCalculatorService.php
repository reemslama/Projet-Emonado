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
                ['min' => 0, 'max' => 4, 'niveau' => 'Excellent', 'couleur' => '#27AE60', 'message' => 'Votre état moral est excellent.'],
                ['min' => 5, 'max' => 8, 'niveau' => 'Bon', 'couleur' => '#2ECC71', 'message' => 'Votre état moral est bon.'],
                ['min' => 9, 'max' => 12, 'niveau' => 'Moyen', 'couleur' => '#F39C12', 'message' => 'Votre état moral est moyen. Prenez soin de vous.'],
                ['min' => 13, 'max' => 16, 'niveau' => 'Préoccupant', 'couleur' => '#E74C3C', 'message' => 'Votre état moral nécessite attention.'],
                ['min' => 17, 'max' => 100, 'niveau' => 'Critique', 'couleur' => '#C0392B', 'message' => 'Nous vous conseillons de consulter un professionnel.'],
            ],
            'stress' => [
                ['min' => 0, 'max' => 3, 'niveau' => 'Faible', 'couleur' => '#27AE60', 'message' => 'Votre niveau de stress est faible.'],
                ['min' => 4, 'max' => 7, 'niveau' => 'Modéré', 'couleur' => '#F39C12', 'message' => 'Votre niveau de stress est modéré.'],
                ['min' => 8, 'max' => 15, 'niveau' => 'Élevé', 'couleur' => '#E74C3C', 'message' => 'Votre niveau de stress est élevé.'],
                ['min' => 16, 'max' => 100, 'niveau' => 'Critique', 'couleur' => '#C0392B', 'message' => 'Votre niveau de stress est critique. Consultez un professionnel.'],
            ],
            'depression' => [
                ['min' => 0, 'max' => 4, 'niveau' => 'Aucun', 'couleur' => '#27AE60', 'message' => 'Aucun signe de dépression détecté.'],
                ['min' => 5, 'max' => 9, 'niveau' => 'Léger', 'couleur' => '#2ECC71', 'message' => 'Symptômes dépressifs légers.'],
                ['min' => 10, 'max' => 14, 'niveau' => 'Modéré', 'couleur' => '#F39C12', 'message' => 'Symptômes dépressifs modérés.'],
                ['min' => 15, 'max' => 100, 'niveau' => 'Sévère', 'couleur' => '#C0392B', 'message' => 'Symptômes dépressifs sévères. Consultation recommandée.'],
            ],
            'anxiete' => [
                ['min' => 0, 'max' => 3, 'niveau' => 'Faible', 'couleur' => '#27AE60', 'message' => 'Niveau d\'anxiété faible.'],
                ['min' => 4, 'max' => 7, 'niveau' => 'Modéré', 'couleur' => '#F39C12', 'message' => 'Niveau d\'anxiété modéré.'],
                ['min' => 8, 'max' => 100, 'niveau' => 'Élevé', 'couleur' => '#E74C3C', 'message' => 'Niveau d\'anxiété élevé. Envisagez une consultation.'],
            ],
            'iq' => [
                ['min' => 0, 'max' => 2, 'niveau' => 'Faible', 'couleur' => '#E74C3C', 'message' => 'Résultats en dessous de la moyenne.'],
                ['min' => 3, 'max' => 5, 'niveau' => 'Moyen', 'couleur' => '#F39C12', 'message' => 'Résultats dans la moyenne.'],
                ['min' => 6, 'max' => 8, 'niveau' => 'Bon', 'couleur' => '#2ECC71', 'message' => 'Bons résultats cognitifs.'],
                ['min' => 9, 'max' => 100, 'niveau' => 'Excellent', 'couleur' => '#27AE60', 'message' => 'Excellentes capacités cognitives.'],
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

    /**
     * Analyse les questions/réponses d'un TestAdaptatif et extrait les scores par catégorie
     * 
     * @param array $questionsReponses Tableau JSON des Q&R du TestAdaptatif
     * @return array Scores par catégorie détectée
     */
    public function analyzeQuestionsReponses(array $questionsReponses): array
    {
        $scoresByCategory = [];
        
        foreach ($questionsReponses as $qr) {
            // Essayer d'identifier la catégorie depuis la question
            $question = $qr['question'] ?? '';
            $valeur = $qr['valeur'] ?? 0;
            
            // Détection simple de catégories via mots-clés
            $categorie = $this->detectCategory($question);
            
            if (!isset($scoresByCategory[$categorie])) {
                $scoresByCategory[$categorie] = 0;
            }
            
            $scoresByCategory[$categorie] += $valeur;
        }
        
        return $scoresByCategory;
    }

    /**
     * Détecte la catégorie d'une question en fonction de mots-clés
     * 
     * @param string $question Texte de la question
     * @return string Catégorie détectée
     */
    private function detectCategory(string $question): string
    {
        $question = strtolower($question);
        
        $keywords = [
            'sommeil' => ['sommeil', 'dormir', 'nuit', 'insomnie', 'réveil'],
            'humeur' => ['humeur', 'moral', 'triste', 'joie', 'émot', 'content'],
            'social' => ['relation', 'ami', 'famille', 'social', 'entourage', 'isolement'],
            'travail' => ['travail', 'emploi', 'professionnel', 'collègue', 'bureau'],
            'anxiété' => ['stress', 'anxiété', 'inquiet', 'nerveux', 'peur', 'angoisse'],
            'énergie' => ['fatigue', 'énergie', 'épuisé', 'motivation', 'activité'],
        ];
        
        foreach ($keywords as $category => $words) {
            foreach ($words as $word) {
                if (str_contains($question, $word)) {
                    return $category;
                }
            }
        }
        
        return 'général';
    }
}
