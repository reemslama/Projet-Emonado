<?php

namespace App\Service;

use App\Entity\TestAdaptatif;

/**
 * Service d'analyse émotionnelle avancée pour les tests adaptatifs
 * Génère des insights détaillés sur l'évolution émotionnelle du patient
 */
class AnalyseEmotionnelleService
{
    /**
     * Analyse complète de l'évolution émotionnelle
     * 
     * @param TestAdaptatif $test
     * @return array Données structurées pour visualisation
     */
    public function analyserEvolution(TestAdaptatif $test): array
    {
        $questionsReponses = $test->getQuestionsReponses();
        
        if (empty($questionsReponses)) {
            return $this->donneesParDefaut();
        }

        return [
            'chronologie' => $this->construireChronologie($questionsReponses),
            'statistiques' => $this->calculerStatistiques($questionsReponses, $test),
            'momentsCritiques' => $this->identifierMomentsCritiques($questionsReponses),
            'tendances' => $this->analyserTendances($questionsReponses),
            'recommendations' => $this->genererRecommendations($questionsReponses, $test),
            'graphiqueData' => $this->preparerDonneesGraphique($questionsReponses)
        ];
    }

    /**
     * Construit une chronologie détaillée de l'évolution
     */
    private function construireChronologie(array $questionsReponses): array
    {
        $chronologie = [];
        $scoresCumules = 0;
        
        foreach ($questionsReponses as $index => $qr) {
            $scoresCumules += $qr['valeur'];
            
            $chronologie[] = [
                'numero' => $index + 1,
                'timestamp' => $qr['timestamp'] ?? null,
                'question' => $qr['question'],
                'reponse' => $qr['reponse'],
                'valeur' => $qr['valeur'],
                'scoreCumule' => $scoresCumules,
                'moyenne' => round($scoresCumules / ($index + 1), 2),
                'niveau' => $this->determinerNiveau($qr['valeur']),
                'couleur' => $this->getCouleurNiveau($qr['valeur'])
            ];
        }
        
        return $chronologie;
    }

    /**
     * Calcule des statistiques avancées
     */
    private function calculerStatistiques(array $questionsReponses, TestAdaptatif $test): array
    {
        $valeurs = array_column($questionsReponses, 'valeur');
        $nbQuestions = count($valeurs);
        
        if ($nbQuestions === 0) {
            return [];
        }

        return [
            'nombreQuestions' => $nbQuestions,
            'scoreTotal' => array_sum($valeurs),
            'scoreMaxPossible' => $nbQuestions * 3,
            'scoreMoyen' => round(array_sum($valeurs) / $nbQuestions, 2),
            'scoreMin' => min($valeurs),
            'scoreMax' => max($valeurs),
            'ecartType' => $this->calculerEcartType($valeurs),
            'variance' => $this->calculerVariance($valeurs),
            'mediane' => $this->calculerMediane($valeurs),
            'distribution' => $this->analyserDistribution($valeurs),
            'coherence' => $this->calculerCoherence($valeurs),
            'duree' => $this->calculerDuree($test),
            'vitesseMoyenne' => $this->calculerVitesseMoyenne($test, $nbQuestions)
        ];
    }

    /**
     * Identifie les moments critiques dans le parcours
     */
    private function identifierMomentsCritiques(array $questionsReponses): array
    {
        $momentsCritiques = [];
        $valeurs = array_column($questionsReponses, 'valeur');
        
        // Identifier les pics (scores élevés)
        foreach ($questionsReponses as $index => $qr) {
            if ($qr['valeur'] >= 2) {
                $momentsCritiques[] = [
                    'type' => 'pic',
                    'numero' => $index + 1,
                    'question' => $qr['question'],
                    'reponse' => $qr['reponse'],
                    'valeur' => $qr['valeur'],
                    'gravite' => $qr['valeur'] === 3 ? 'critique' : 'preoccupant',
                    'description' => $this->descriptionMomentCritique($qr)
                ];
            }
        }
        
        // Identifier les changements brusques
        for ($i = 1; $i < count($valeurs); $i++) {
            $variation = abs($valeurs[$i] - $valeurs[$i - 1]);
            
            if ($variation >= 2) {
                $momentsCritiques[] = [
                    'type' => 'changement_brusque',
                    'numero' => $i + 1,
                    'variation' => $variation,
                    'direction' => $valeurs[$i] > $valeurs[$i - 1] ? 'aggravation' : 'amelioration',
                    'description' => "Changement brusque de niveau émotionnel détecté"
                ];
            }
        }
        
        // Identifier les séquences critiques (3+ réponses élevées consécutives)
        $sequenceCritique = 0;
        for ($i = 0; $i < count($valeurs); $i++) {
            if ($valeurs[$i] >= 2) {
                $sequenceCritique++;
                if ($sequenceCritique >= 3 && ($i === count($valeurs) - 1 || $valeurs[$i + 1] < 2)) {
                    $momentsCritiques[] = [
                        'type' => 'sequence_critique',
                        'debut' => $i - $sequenceCritique + 2,
                        'fin' => $i + 1,
                        'longueur' => $sequenceCritique,
                        'description' => "Série de $sequenceCritique réponses préoccupantes consécutives"
                    ];
                }
            } else {
                $sequenceCritique = 0;
            }
        }
        
        return $momentsCritiques;
    }

    /**
     * Analyse les tendances émotionnelles
     */
    private function analyserTendances(array $questionsReponses): array
    {
        $valeurs = array_column($questionsReponses, 'valeur');
        $nbQuestions = count($valeurs);
        
        if ($nbQuestions < 3) {
            return ['type' => 'insuffisant', 'description' => 'Pas assez de données'];
        }

        // Calcul de la pente (régression linéaire simple)
        $pente = $this->calculerPente($valeurs);
        
        // Déterminer la tendance
        $tendanceType = 'stable';
        $tendanceDescription = 'Votre état émotionnel est resté relativement stable';
        
        if ($pente > 0.15) {
            $tendanceType = 'aggravation';
            $tendanceDescription = 'Tendance à l\'aggravation détectée au fil du test';
        } elseif ($pente < -0.15) {
            $tendanceType = 'amelioration';
            $tendanceDescription = 'Tendance à l\'amélioration détectée au fil du test';
        }
        
        // Analyser la régularité
        $regularite = $this->analyserRegularite($valeurs);
        
        return [
            'type' => $tendanceType,
            'pente' => round($pente, 3),
            'description' => $tendanceDescription,
            'regularite' => $regularite,
            'conseils' => $this->genererConseilsTendance($tendanceType, $regularite)
        ];
    }

    /**
     * Génère des recommandations basées sur l'analyse
     */
    private function genererRecommendations(array $questionsReponses, TestAdaptatif $test): array
    {
        $recommendations = [];
        $valeurs = array_column($questionsReponses, 'valeur');
        $moyenne = array_sum($valeurs) / count($valeurs);
        
        // Recommandations selon le niveau global
        if ($moyenne >= 2.5) {
            $recommendations[] = [
                'priorite' => 'urgente',
                'icone' => 'fa-heart-pulse',
                'titre' => 'Consultation Professionnelle Urgente',
                'description' => 'Vos réponses indiquent un niveau de détresse élevé. Une consultation rapide est fortement recommandée.',
                'actions' => [
                    'Prendre rendez-vous avec un psychologue dès aujourd\'hui',
                    'Contacter une ligne d\'écoute: 0800 808 005 (24h/24)',
                    'En parler à un proche de confiance'
                ]
            ];
        } elseif ($moyenne >= 1.5) {
            $recommendations[] = [
                'priorite' => 'importante',
                'icone' => 'fa-user-doctor',
                'titre' => 'Suivi Professionnel Recommandé',
                'description' => 'Un accompagnement psychologique pourrait vous être bénéfique.',
                'actions' => [
                    'Planifier une consultation dans les 2 semaines',
                    'Tenir un journal émotionnel quotidien',
                    'Pratiquer des techniques de relaxation'
                ]
            ];
        }
        
        // Recommandations selon les moments critiques
        $momentsCritiques = $this->identifierMomentsCritiques($questionsReponses);
        if (count($momentsCritiques) >= 3) {
            $recommendations[] = [
                'priorite' => 'importante',
                'icone' => 'fa-chart-line',
                'titre' => 'Fluctuations Émotionnelles Importantes',
                'description' => 'Vos réponses montrent des variations significatives.',
                'actions' => [
                    'Identifier les déclencheurs émotionnels',
                    'Apprendre des techniques de régulation émotionnelle',
                    'Consulter pour un diagnostic plus précis'
                ]
            ];
        }
        
        // Recommandations pratiques
        $recommendations[] = [
            'priorite' => 'normale',
            'icone' => 'fa-heartbeat',
            'titre' => 'Pratiques de Bien-être Quotidien',
            'description' => 'Intégrez ces habitudes dans votre routine.',
            'actions' => [
                'Exercice physique régulier (30min/jour)',
                'Méditation ou pleine conscience (10min/jour)',
                'Sommeil régulier (7-8h/nuit)',
                'Limiter la caféine et les écrans avant le coucher'
            ]
        ];
        
        return $recommendations;
    }

    /**
     * Prépare les données pour le graphique Chart.js
     */
    private function preparerDonneesGraphique(array $questionsReponses): array
    {
        $labels = [];
        $scores = [];
        $scoresCumules = [];
        $couleurs = [];
        $cumul = 0;
        
        foreach ($questionsReponses as $index => $qr) {
            $labels[] = "Q" . ($index + 1);
            $scores[] = $qr['valeur'];
            $cumul += $qr['valeur'];
            $scoresCumules[] = $cumul;
            $couleurs[] = $this->getCouleurGraphique($qr['valeur']);
        }
        
        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Score par question',
                    'data' => $scores,
                    'borderColor' => 'rgb(75, 192, 192)',
                    'backgroundColor' => $couleurs,
                    'tension' => 0.4,
                    'fill' => false,
                    'type' => 'line'
                ],
                [
                    'label' => 'Score cumulé',
                    'data' => $scoresCumules,
                    'borderColor' => 'rgb(255, 99, 132)',
                    'backgroundColor' => 'rgba(255, 99, 132, 0.1)',
                    'tension' => 0.4,
                    'fill' => true,
                    'type' => 'line'
                ]
            ],
            'annotations' => $this->genererAnnotationsGraphique($questionsReponses)
        ];
    }

    // ==================== MÉTHODES UTILITAIRES ====================

    private function determinerNiveau(int $valeur): string
    {
        return match($valeur) {
            0 => 'Faible',
            1 => 'Modéré',
            2 => 'Préoccupant',
            3 => 'Critique',
            default => 'Inconnu'
        };
    }

    private function getCouleurNiveau(int $valeur): string
    {
        return match($valeur) {
            0 => 'success',
            1 => 'info',
            2 => 'warning',
            3 => 'danger',
            default => 'secondary'
        };
    }

    private function getCouleurGraphique(int $valeur): string
    {
        return match($valeur) {
            0 => 'rgba(40, 167, 69, 0.6)',
            1 => 'rgba(23, 162, 184, 0.6)',
            2 => 'rgba(255, 193, 7, 0.6)',
            3 => 'rgba(220, 53, 69, 0.6)',
            default => 'rgba(108, 117, 125, 0.6)'
        };
    }

    private function calculerEcartType(array $valeurs): float
    {
        $variance = $this->calculerVariance($valeurs);
        return round(sqrt($variance), 2);
    }

    private function calculerVariance(array $valeurs): float
    {
        $moyenne = array_sum($valeurs) / count($valeurs);
        $ecarts = array_map(fn($v) => pow($v - $moyenne, 2), $valeurs);
        return round(array_sum($ecarts) / count($valeurs), 2);
    }

    private function calculerMediane(array $valeurs): float
    {
        sort($valeurs);
        $count = count($valeurs);
        $middle = floor($count / 2);
        
        if ($count % 2 == 0) {
            return ($valeurs[$middle - 1] + $valeurs[$middle]) / 2;
        }
        
        return $valeurs[$middle];
    }

    private function analyserDistribution(array $valeurs): array
    {
        $distribution = [0 => 0, 1 => 0, 2 => 0, 3 => 0];
        
        foreach ($valeurs as $valeur) {
            $distribution[$valeur] = ($distribution[$valeur] ?? 0) + 1;
        }
        
        $total = count($valeurs);
        
        return [
            'faible' => ['count' => $distribution[0], 'pourcentage' => round($distribution[0] / $total * 100, 1)],
            'modere' => ['count' => $distribution[1], 'pourcentage' => round($distribution[1] / $total * 100, 1)],
            'preoccupant' => ['count' => $distribution[2], 'pourcentage' => round($distribution[2] / $total * 100, 1)],
            'critique' => ['count' => $distribution[3], 'pourcentage' => round($distribution[3] / $total * 100, 1)]
        ];
    }

    private function calculerCoherence(array $valeurs): array
    {
        $ecartType = $this->calculerEcartType($valeurs);
        $moyenne = array_sum($valeurs) / count($valeurs);
        
        $coherence = 'elevee';
        $description = 'Vos réponses sont cohérentes entre elles';
        
        if ($ecartType > 1.2) {
            $coherence = 'faible';
            $description = 'Vos réponses montrent une grande variabilité';
        } elseif ($ecartType > 0.8) {
            $coherence = 'moyenne';
            $description = 'Vos réponses montrent une certaine variabilité';
        }
        
        return [
            'niveau' => $coherence,
            'description' => $description,
            'ecartType' => $ecartType
        ];
    }

    private function calculerDuree(TestAdaptatif $test): ?int
    {
        $debut = $test->getDateDebut();
        $fin = $test->getDateFin();
        
        if (!$debut || !$fin) {
            return null;
        }
        
        return $fin->getTimestamp() - $debut->getTimestamp();
    }

    private function calculerVitesseMoyenne(TestAdaptatif $test, int $nbQuestions): ?float
    {
        $duree = $this->calculerDuree($test);
        
        if (!$duree || $nbQuestions === 0) {
            return null;
        }
        
        return round($duree / $nbQuestions, 1);
    }

    private function calculerPente(array $valeurs): float
    {
        $n = count($valeurs);
        $sumX = 0;
        $sumY = array_sum($valeurs);
        $sumXY = 0;
        $sumX2 = 0;
        
        foreach ($valeurs as $x => $y) {
            $sumX += $x;
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
        }
        
        $pente = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        
        return $pente;
    }

    private function analyserRegularite(array $valeurs): array
    {
        $ecartType = $this->calculerEcartType($valeurs);
        
        if ($ecartType < 0.5) {
            return [
                'niveau' => 'tres_regulier',
                'description' => 'Très régulier',
                'conseil' => 'Vos réponses montrent une grande stabilité émotionnelle'
            ];
        } elseif ($ecartType < 1.0) {
            return [
                'niveau' => 'regulier',
                'description' => 'Régulier',
                'conseil' => 'Vos réponses montrent une stabilité émotionnelle modérée'
            ];
        } else {
            return [
                'niveau' => 'irregulier',
                'description' => 'Irrégulier',
                'conseil' => 'Vos réponses montrent des fluctuations importantes'
            ];
        }
    }

    private function genererConseilsTendance(string $tendance, array $regularite): array
    {
        $conseils = [];
        
        if ($tendance === 'aggravation') {
            $conseils[] = "Surveillez attentivement l'évolution de votre état";
            $conseils[] = "Envisagez une consultation rapide";
            $conseils[] = "Identifiez les facteurs déclenchants récents";
        } elseif ($tendance === 'amelioration') {
            $conseils[] = "Continuez les pratiques qui vous font du bien";
            $conseils[] = "Maintenez vos bonnes habitudes";
            $conseils[] = "Restez vigilant malgré l'amélioration";
        } else {
            $conseils[] = "Maintenez un suivi régulier";
            $conseils[] = "Soyez attentif aux changements";
        }
        
        if ($regularite['niveau'] === 'irregulier') {
            $conseils[] = "Travaillez sur la régulation émotionnelle";
        }
        
        return $conseils;
    }

    private function descriptionMomentCritique(array $qr): string
    {
        $valeur = $qr['valeur'];
        
        if ($valeur === 3) {
            return "Niveau critique identifié - nécessite une attention particulière";
        } elseif ($valeur === 2) {
            return "Niveau préoccupant - à surveiller attentivement";
        }
        
        return "Point d'attention";
    }

    private function genererAnnotationsGraphique(array $questionsReponses): array
    {
        $annotations = [];
        
        foreach ($questionsReponses as $index => $qr) {
            if ($qr['valeur'] >= 2) {
                $annotations[] = [
                    'type' => 'point',
                    'xValue' => $index,
                    'yValue' => $qr['valeur'],
                    'backgroundColor' => $qr['valeur'] === 3 ? 'rgba(220, 53, 69, 0.8)' : 'rgba(255, 193, 7, 0.8)',
                    'radius' => 8,
                    'borderWidth' => 2,
                    'borderColor' => 'white'
                ];
            }
        }
        
        return $annotations;
    }

    private function donneesParDefaut(): array
    {
        return [
            'chronologie' => [],
            'statistiques' => [],
            'momentsCritiques' => [],
            'tendances' => ['type' => 'insuffisant'],
            'recommendations' => [],
            'graphiqueData' => [
                'labels' => [],
                'datasets' => [],
                'annotations' => []
            ]
        ];
    }
}
