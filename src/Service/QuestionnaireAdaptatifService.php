<?php

namespace App\Service;

use App\Entity\TestAdaptatif;
use App\Entity\User;
use Psr\Log\LoggerInterface;

class QuestionnaireAdaptatifService
{
    private const SEUIL_CRITIQUE = 3; // Score par question indiquant un problème
    private const MIN_QUESTIONS = 3;
    private const MAX_QUESTIONS = 10;

    public function __construct(
        private GroqAiService $groqAiService,
        private LoggerInterface $logger
    ) {}

    /**
     * Génère la prochaine question basée sur l'historique
     */
    public function genererProchaineQuestion(TestAdaptatif $test): ?array
    {
        $categorie = $test->getCategorie();
        $questionsReponses = $test->getQuestionsReponses();
        $nombreQuestions = count($questionsReponses);
        $scoreActuel = $test->getScoreActuel();
        $profilPatient = $test->getProfilPatient() ?? [];

        // Vérifier si on doit arrêter le test
        if ($this->doitArreterTest($test)) {
            return null;
        }

        // Déterminer le type de question
        $typeQuestion = 'standard';
        if ($nombreQuestions === 0) {
            $typeQuestion = 'initial';
        } elseif (end($questionsReponses)['valeur'] >= self::SEUIL_CRITIQUE) {
            $typeQuestion = 'approfondissement';
        }

        // Analyser la tendance pour arrêt anticipé
        $tendance = $this->analyserTendance($questionsReponses);
        if ($tendance === 'stable_faible' && $nombreQuestions >= self::MIN_QUESTIONS) {
            return null;
        }

        // Utiliser Groq AI si configuré, sinon fallback
        if ($this->groqAiService->isConfigured()) {
            try {
                $question = $this->groqAiService->genererQuestionPsychologique(
                    $categorie,
                    $questionsReponses,
                    $profilPatient,
                    $typeQuestion
                );
                
                $this->logger->info('Question générée par Groq AI', [
                    'categorie' => $categorie,
                    'type' => $typeQuestion,
                    'question' => $question['texte']
                ]);
                
                return $question;
            } catch (\Exception $e) {
                $this->logger->error('Erreur Groq AI, fallback vers questions par défaut: ' . $e->getMessage());
                return $this->getQuestionFallback($categorie, $typeQuestion, $questionsReponses, $profilPatient);
            }
        }

        // Fallback si Groq n'est pas configuré
        return $this->getQuestionFallback($categorie, $typeQuestion, $questionsReponses, $profilPatient);
    }

    /**
     * Analyse la tendance générale des réponses
     */
    private function analyserTendance(array $questionsReponses): string
    {
        if (empty($questionsReponses)) {
            return 'neutre';
        }

        $valeurs = array_column($questionsReponses, 'valeur');
        $moyenne = array_sum($valeurs) / count($valeurs);

        if ($moyenne >= 2.5) {
            return 'critique';
        } elseif ($moyenne >= 1.5) {
            return 'preoccupant';
        } elseif ($moyenne >= 0.8) {
            return 'modere';
        } else {
            return 'stable_faible';
        }
    }

    /**
     * Détermine si on doit arrêter le test
     */
    private function doitArreterTest(TestAdaptatif $test): bool
    {
        $nombreQuestions = count($test->getQuestionsReponses());
        $tendance = $this->analyserTendance($test->getQuestionsReponses());

        // Arrêt anticipé si tout va bien
        if ($nombreQuestions >= self::MIN_QUESTIONS && $tendance === 'stable_faible') {
            return true;
        }

        // Arrêt à la limite max
        if ($nombreQuestions >= self::MAX_QUESTIONS) {
            return true;
        }

        return false;
    }

    /**
     * Question fallback (si Groq n'est pas disponible)
     */
    private function getQuestionFallback(string $categorie, string $typeQuestion, array $questionsReponses, array $profil): array
    {
        if ($typeQuestion === 'initial') {
            return $this->getQuestionInitiale($categorie, $profil);
        } elseif ($typeQuestion === 'approfondissement') {
            $derniereReponse = end($questionsReponses);
            return $this->genererQuestionApprofondissement($categorie, $derniereReponse, $profil);
        } else {
            $nombreQuestions = count($questionsReponses);
            return $this->genererQuestionSuivante($categorie, $nombreQuestions, $questionsReponses, $profil);
        }
    }

    /**
     * Question initiale selon la catégorie
     */
    private function getQuestionInitiale(string $categorie, array $profil): array
    {
        $nom = $profil['nom'] ?? 'vous';
        
        $questions = [
            'stress' => [
                'texte' => "Bonjour $nom, comment évaluez-vous votre niveau de stress actuellement ?",
                'reponses' => [
                    ['texte' => 'Je me sens bien, pas de stress particulier', 'valeur' => 0],
                    ['texte' => 'Un peu stressé(e), rien de grave', 'valeur' => 1],
                    ['texte' => 'Assez stressé(e), ça commence à peser', 'valeur' => 2],
                    ['texte' => 'Très stressé(e), je ne tiens plus', 'valeur' => 3],
                ]
            ],
            'depression' => [
                'texte' => "Bonjour $nom, comment décririez-vous votre humeur ces derniers temps ?",
                'reponses' => [
                    ['texte' => 'Bonne humeur générale', 'valeur' => 0],
                    ['texte' => 'Quelques moments de tristesse passagers', 'valeur' => 1],
                    ['texte' => 'Souvent triste ou vide', 'valeur' => 2],
                    ['texte' => 'Tristesse persistante et profonde', 'valeur' => 3],
                ]
            ],
            'iq' => [
                'texte' => "Commençons par une question simple : combien font 2 + 2 ?",
                'reponses' => [
                    ['texte' => '3', 'valeur' => 0],
                    ['texte' => '4', 'valeur' => 1],
                    ['texte' => '5', 'valeur' => 0],
                    ['texte' => '22', 'valeur' => 0],
                ]
            ],
        ];

        return $questions[$categorie] ?? $questions['stress'];
    }

    /**
     * Génère une question d'approfondissement
     */
    private function genererQuestionApprofondissement(string $categorie, array $derniereReponse, array $profil): array
    {
        $questions = [
            'stress' => [
                [
                    'texte' => "Je comprends que votre stress est important. Depuis combien de temps ressentez-vous cela ?",
                    'reponses' => [
                        ['texte' => 'Quelques jours', 'valeur' => 1],
                        ['texte' => 'Quelques semaines', 'valeur' => 2],
                        ['texte' => 'Plusieurs mois', 'valeur' => 3],
                        ['texte' => 'Plus d\'un an', 'valeur' => 3],
                    ]
                ],
                [
                    'texte' => "Ce stress a-t-il un impact sur votre sommeil ?",
                    'reponses' => [
                        ['texte' => 'Non, je dors normalement', 'valeur' => 0],
                        ['texte' => 'Parfois difficile de m\'endormir', 'valeur' => 1],
                        ['texte' => 'Je me réveille souvent la nuit', 'valeur' => 2],
                        ['texte' => 'Insomnie sévère, je dors très peu', 'valeur' => 3],
                    ]
                ],
                [
                    'texte' => "Avez-vous identifié la source principale de votre stress ?",
                    'reponses' => [
                        ['texte' => 'Oui, c\'est lié au travail', 'valeur' => 2],
                        ['texte' => 'Oui, c\'est personnel/familial', 'valeur' => 2],
                        ['texte' => 'Oui, c\'est financier', 'valeur' => 2],
                        ['texte' => 'Non, je ne sais pas pourquoi', 'valeur' => 3],
                    ]
                ],
            ],
            'depression' => [
                [
                    'texte' => "Cette tristesse affecte-t-elle vos activités quotidiennes ?",
                    'reponses' => [
                        ['texte' => 'Non, je fonctionne normalement', 'valeur' => 0],
                        ['texte' => 'Un peu, mais je m\'en sors', 'valeur' => 1],
                        ['texte' => 'Oui, j\'ai du mal à faire les choses', 'valeur' => 2],
                        ['texte' => 'Oui, je ne peux presque plus rien faire', 'valeur' => 3],
                    ]
                ],
                [
                    'texte' => "Avez-vous perdu de l'intérêt pour les choses que vous aimiez ?",
                    'reponses' => [
                        ['texte' => 'Non, je garde mes passions', 'valeur' => 0],
                        ['texte' => 'Un peu moins d\'enthousiasme', 'valeur' => 1],
                        ['texte' => 'Oui, beaucoup de choses ne m\'intéressent plus', 'valeur' => 2],
                        ['texte' => 'Plus rien ne me fait plaisir', 'valeur' => 3],
                    ]
                ],
                [
                    'texte' => "Vous sentez-vous fatigué(e) même sans effort ?",
                    'reponses' => [
                        ['texte' => 'Non, j\'ai de l\'énergie', 'valeur' => 0],
                        ['texte' => 'Parfois un peu fatigué(e)', 'valeur' => 1],
                        ['texte' => 'Oui, souvent épuisé(e)', 'valeur' => 2],
                        ['texte' => 'Fatigue constante et extrême', 'valeur' => 3],
                    ]
                ],
            ],
            'iq' => [
                [
                    'texte' => "Quelle est la suite logique : 2, 4, 8, 16, ... ?",
                    'reponses' => [
                        ['texte' => '24', 'valeur' => 0],
                        ['texte' => '32', 'valeur' => 1],
                        ['texte' => '20', 'valeur' => 0],
                        ['texte' => '18', 'valeur' => 0],
                    ]
                ],
            ],
        ];

        $questionsCategorie = $questions[$categorie] ?? $questions['stress'];
        $index = count($derniereReponse) % count($questionsCategorie);
        
        return $questionsCategorie[$index];
    }

    /**
     * Génère la question suivante standard
     */
    private function genererQuestionSuivante(string $categorie, int $numero, array $historique, array $profil): array
    {
        $questions = [
            'stress' => [
                [
                    'texte' => "Ressentez-vous des tensions physiques (maux de tête, douleurs, etc.) ?",
                    'reponses' => [
                        ['texte' => 'Non, aucune tension', 'valeur' => 0],
                        ['texte' => 'Parfois quelques tensions', 'valeur' => 1],
                        ['texte' => 'Oui, souvent des douleurs', 'valeur' => 2],
                        ['texte' => 'Douleurs constantes et invalidantes', 'valeur' => 3],
                    ]
                ],
                [
                    'texte' => "Arrivez-vous à vous détendre et à profiter de moments de repos ?",
                    'reponses' => [
                        ['texte' => 'Oui, je me détends facilement', 'valeur' => 0],
                        ['texte' => 'Parfois, mais pas toujours', 'valeur' => 1],
                        ['texte' => 'Rarement, j\'ai du mal à lâcher prise', 'valeur' => 2],
                        ['texte' => 'Jamais, je suis toujours tendu(e)', 'valeur' => 3],
                    ]
                ],
                [
                    'texte' => "Vous sentez-vous irritable ou sur les nerfs ?",
                    'reponses' => [
                        ['texte' => 'Non, je suis calme', 'valeur' => 0],
                        ['texte' => 'Parfois un peu irritable', 'valeur' => 1],
                        ['texte' => 'Souvent irritable', 'valeur' => 2],
                        ['texte' => 'Constamment à bout de nerfs', 'valeur' => 3],
                    ]
                ],
            ],
            'depression' => [
                [
                    'texte' => "Comment est votre appétit dernièrement ?",
                    'reponses' => [
                        ['texte' => 'Normal', 'valeur' => 0],
                        ['texte' => 'Légèrement modifié', 'valeur' => 1],
                        ['texte' => 'Beaucoup plus ou moins qu\'avant', 'valeur' => 2],
                        ['texte' => 'Presque plus d\'appétit ou boulimie', 'valeur' => 3],
                    ]
                ],
                [
                    'texte' => "Vous sentez-vous coupable ou sans valeur ?",
                    'reponses' => [
                        ['texte' => 'Non, pas du tout', 'valeur' => 0],
                        ['texte' => 'Parfois quelques doutes', 'valeur' => 1],
                        ['texte' => 'Souvent des sentiments de culpabilité', 'valeur' => 2],
                        ['texte' => 'Culpabilité intense et constante', 'valeur' => 3],
                    ]
                ],
                [
                    'texte' => "Avez-vous des difficultés à vous concentrer ?",
                    'reponses' => [
                        ['texte' => 'Non, concentration normale', 'valeur' => 0],
                        ['texte' => 'Parfois distrait(e)', 'valeur' => 1],
                        ['texte' => 'Difficultés fréquentes', 'valeur' => 2],
                        ['texte' => 'Impossible de me concentrer', 'valeur' => 3],
                    ]
                ],
            ],
            'iq' => [
                [
                    'texte' => "Chat est à Miauler comme Chien est à :",
                    'reponses' => [
                        ['texte' => 'Courir', 'valeur' => 0],
                        ['texte' => 'Aboyer', 'valeur' => 1],
                        ['texte' => 'Manger', 'valeur' => 0],
                        ['texte' => 'Dormir', 'valeur' => 0],
                    ]
                ],
                [
                    'texte' => "Quel nombre complète la série : 1, 1, 2, 3, 5, 8, ... ?",
                    'reponses' => [
                        ['texte' => '11', 'valeur' => 0],
                        ['texte' => '13', 'valeur' => 1],
                        ['texte' => '12', 'valeur' => 0],
                        ['texte' => '10', 'valeur' => 0],
                    ]
                ],
            ],
        ];

        $questionsCategorie = $questions[$categorie] ?? $questions['stress'];
        $index = $numero % count($questionsCategorie);
        
        return $questionsCategorie[$index];
    }

    /**
     * Génère une analyse finale du test
     */
    /**
     * Génère une analyse finale du test
     */
    public function genererAnalyseFinale(TestAdaptatif $test): string
    {
        $score = $test->getScoreActuel();
        $nombreQuestions = $test->getNombreQuestions();
        $categorie = $test->getCategorie();
        $questionsReponses = $test->getQuestionsReponses();

        // Utiliser Groq AI si configuré
        if ($this->groqAiService->isConfigured()) {
            try {
                $analyse = $this->groqAiService->genererAnalyse(
                    $categorie,
                    $questionsReponses,
                    $score,
                    $nombreQuestions
                );
                
                $this->logger->info('Analyse générée par Groq AI', [
                    'categorie' => $categorie,
                    'score' => $score,
                    'questions' => $nombreQuestions
                ]);
                
                return $analyse;
            } catch (\Exception $e) {
                $this->logger->error('Erreur génération analyse Groq: ' . $e->getMessage());
                // Fallback vers analyse par défaut
            }
        }

        // Analyse par défaut (fallback)
        $moyenneParQuestion = $nombreQuestions > 0 ? $score / $nombreQuestions : 0;
        $tendance = $this->analyserTendance($questionsReponses);

        $analyse = "Analyse du test de $categorie :\n\n";
        $analyse .= "Score total : $score sur un maximum de " . ($nombreQuestions * 3) . " points\n";
        $analyse .= "Nombre de questions posées : $nombreQuestions\n";
        $analyse .= "Moyenne par question : " . number_format($moyenneParQuestion, 2) . "\n\n";

        // Analyse selon la tendance
        switch ($tendance) {
            case 'critique':
                $analyse .= "⚠️ NIVEAU CRITIQUE : Vos réponses indiquent une situation préoccupante nécessitant une attention immédiate.\n";
                $analyse .= "Recommandation : Consultez un professionnel de santé dès que possible.\n";
                break;
            case 'preoccupant':
                $analyse .= "⚠️ NIVEAU PRÉOCCUPANT : Plusieurs de vos réponses indiquent des difficultés significatives.\n";
                $analyse .= "Recommandation : Envisagez de consulter un professionnel pour un suivi.\n";
                break;
            case 'modere':
                $analyse .= "ℹ️ NIVEAU MODÉRÉ : Vous présentez quelques signes à surveiller.\n";
                $analyse .= "Recommandation : Prenez soin de vous et surveillez l'évolution.\n";
                break;
            default:
                $analyse .= "✅ NIVEAU FAIBLE : Vos réponses indiquent un état globalement satisfaisant.\n";
                $analyse .= "Recommandation : Maintenez vos bonnes habitudes.\n";
        }

        // Analyse des réponses critiques
        $reponsesPreoccupantes = array_filter($questionsReponses, fn($qr) => $qr['valeur'] >= 2);
        if (count($reponsesPreoccupantes) > 0) {
            $analyse .= "\nPoints d'attention particuliers : " . count($reponsesPreoccupantes) . " question(s) avec score élevé.\n";
        }

        return $analyse;
    }

    /**
     * Extrait le profil du patient si connecté
     */
    public function extraireProfilPatient(?User $user): array
    {
        if (!$user) {
            return ['nom' => 'cher patient'];
        }

        return [
            'nom' => $user->getPrenom() ?? 'cher patient',
            'age' => null, // À ajouter dans l'entité User si nécessaire
            'email' => $user->getEmail(),
        ];
    }
}
