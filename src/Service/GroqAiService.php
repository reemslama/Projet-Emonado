<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class GroqAiService
{
    private const GROQ_API_URL = 'https://api.groq.com/openai/v1/chat/completions';
    private const DEFAULT_MODEL = 'llama-3.3-70b-versatile'; // Modèle le plus rapide et performant

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private string $groqApiKey
    ) {}

    /**
     * Génère une question psychologique adaptée au contexte
     */
    public function genererQuestionPsychologique(
        string $categorie,
        array $historique = [],
        array $profilPatient = [],
        string $typeQuestion = 'initial'
    ): array {
        $systemPrompt = $this->construireSystemPrompt($categorie);
        $userPrompt = $this->construireUserPrompt($categorie, $historique, $profilPatient, $typeQuestion);

        try {
            $response = $this->appelGroqApi($systemPrompt, $userPrompt);
            return $this->parseResponse($response);
        } catch (\Exception $e) {
            $this->logger->error('Erreur Groq API: ' . $e->getMessage());
            // Fallback vers une question par défaut
            return $this->getQuestionParDefaut($categorie, $typeQuestion);
        }
    }

    /**
     * Génère une analyse détaillée du test
     */
    public function genererAnalyse(
        string $categorie,
        array $questionsReponses,
        int $score,
        int $nombreQuestions
    ): string {
        $systemPrompt = "Tu es un psychologue professionnel spécialisé en analyse psychométrique. Tu analyses les résultats de tests psychologiques avec empathie et précision.";
        
        $userPrompt = "Analyse ce test de $categorie :\n\n";
        $userPrompt .= "Score total: $score sur " . ($nombreQuestions * 3) . " points\n";
        $userPrompt .= "Nombre de questions: $nombreQuestions\n\n";
        $userPrompt .= "Historique des réponses:\n";
        
        foreach ($questionsReponses as $index => $qr) {
            $userPrompt .= ($index + 1) . ". Q: " . $qr['question'] . "\n";
            $userPrompt .= "   R: " . $qr['reponse'] . " (score: " . $qr['valeur'] . ")\n\n";
        }
        
        $userPrompt .= "\nFournis une analyse complète comprenant:\n";
        $userPrompt .= "1. Évaluation globale du niveau\n";
        $userPrompt .= "2. Points d'attention particuliers\n";
        $userPrompt .= "3. Recommandations personnalisées\n";
        $userPrompt .= "4. Conseils pratiques\n\n";
        $userPrompt .= "Sois empathique, professionnel et encourageant.";

        try {
            $response = $this->appelGroqApi($systemPrompt, $userPrompt, 0.7, 1000);
            return $response['choices'][0]['message']['content'] ?? 'Analyse non disponible';
        } catch (\Exception $e) {
            $this->logger->error('Erreur génération analyse: ' . $e->getMessage());
            return $this->getAnalyseParDefaut($categorie, $score, $nombreQuestions);
        }
    }

    /**
     * Construit le prompt système selon la catégorie
     */
    private function construireSystemPrompt(string $categorie): string
    {
        $basePrompt = "Tu es un psychologue virtuel empathique et professionnel. Tu poses des questions pour évaluer ";
        
        switch ($categorie) {
            case 'stress':
                $basePrompt .= "le niveau de stress du patient. Tes questions doivent être bienveillantes, directes et aider à identifier les sources et l'intensité du stress.";
                break;
            case 'depression':
                $basePrompt .= "les symptômes dépressifs du patient. Tes questions doivent être sensibles, non-jugeantes et explorer l'humeur, l'énergie et les activités quotidiennes.";
                break;
            case 'iq':
                $basePrompt .= "les capacités cognitives et le raisonnement logique du patient. Tes questions doivent être des énigmes, problèmes de logique ou suites à compléter.";
                break;
            default:
                $basePrompt .= "l'état psychologique général du patient.";
        }
        
        $basePrompt .= "\n\nDIRECTIVES IMPORTANTES:\n";
        $basePrompt .= "- Pose UNE SEULE question à la fois\n";
        $basePrompt .= "- Fournis EXACTEMENT 4 options de réponse\n";
        $basePrompt .= "- Chaque option doit avoir un score de 0 à 3 (0=pas de problème, 3=problème sévère)\n";
        $basePrompt .= "- Pour les tests IQ: 0=mauvaise réponse, 1=bonne réponse\n";
        $basePrompt .= "- Utilise un ton conversationnel et naturel\n";
        $basePrompt .= "- Adapte-toi au contexte fourni (historique des réponses précédentes)\n";
        $basePrompt .= "- ⚠️ IMPÉRATIF: Ne JAMAIS répéter un thème déjà exploré\n";
        $basePrompt .= "- Si des thèmes sont listés comme 'déjà abordés', trouve quelque chose de COMPLÈTEMENT différent\n\n";
        $basePrompt .= "FORMAT DE RÉPONSE OBLIGATOIRE (JSON):\n";
        $basePrompt .= "{\n";
        $basePrompt .= '  "question": "Ta question ici",'."\n";
        $basePrompt .= '  "reponses": ['."\n";
        $basePrompt .= '    {"texte": "Option A", "valeur": 0},'."\n";
        $basePrompt .= '    {"texte": "Option B", "valeur": 1},'."\n";
        $basePrompt .= '    {"texte": "Option C", "valeur": 2},'."\n";
        $basePrompt .= '    {"texte": "Option D", "valeur": 3}'."\n";
        $basePrompt .= "  ]\n";
        $basePrompt .= "}";
        
        return $basePrompt;
    }

    /**
     * Construit le prompt utilisateur avec le contexte
     */
    private function construireUserPrompt(
        string $categorie,
        array $historique,
        array $profilPatient,
        string $typeQuestion
    ): string {
        $nom = $profilPatient['nom'] ?? 'cher patient';
        $prompt = "";
        
        if ($typeQuestion === 'initial') {
            $prompt = "Génère une question d'introduction pour un test de $categorie pour $nom. ";
            $prompt .= "Cette question doit être générale et permettre d'évaluer le niveau global. ";
            $prompt .= "Sois accueillant et rassurant.";
        } elseif ($typeQuestion === 'approfondissement') {
            $derniereReponse = end($historique);
            $prompt = "Le patient a répondu à cette question:\n\n";
            $prompt .= "Q: " . $derniereReponse['question'] . "\n";
            $prompt .= "R: " . $derniereReponse['reponse'] . " (score: " . $derniereReponse['valeur'] . ")\n\n";
            $prompt .= "Son score indique un niveau préoccupant. Génère une question d'APPROFONDISSEMENT pour mieux comprendre cette problématique. ";
            $prompt .= "La question doit explorer les détails, la durée, l'impact ou les causes de ce problème.";
        } else {
            // Question standard avec extraction des thèmes déjà abordés
            $themesAbordes = $this->extraireThemesAbordes($historique, $categorie);
            
            $prompt = "Basé sur cet historique:\n\n";
            foreach ($historique as $index => $qr) {
                $prompt .= ($index + 1) . ". Q: " . $qr['question'] . "\n";
                $prompt .= "   R: " . $qr['reponse'] . " (score: " . $qr['valeur'] . ")\n\n";
            }
            
            $prompt .= "\n⚠️ THÈMES DÉJÀ ABORDÉS (NE PAS RÉPÉTER):\n";
            foreach ($themesAbordes as $theme) {
                $prompt .= "- " . $theme . "\n";
            }
            
            $prompt .= "\n📋 INSTRUCTIONS:\n";
            $prompt .= "1. Génère une question COMPLÈTEMENT DIFFÉRENTE des précédentes\n";
            $prompt .= "2. N'utilise AUCUN des thèmes listés ci-dessus\n";
            $prompt .= "3. Explore un NOUVEL aspect du $categorie non encore abordé\n";
            $prompt .= "4. Sois créatif et original tout en restant pertinent\n";
        }
        
        return $prompt;
    }

    /**
     * Extrait les thèmes déjà abordés dans l'historique
     */
    private function extraireThemesAbordes(array $historique, string $categorie): array
    {
        $themes = [];
        
        foreach ($historique as $qr) {
            $question = strtolower($qr['question']);
            
            // Mots-clés par catégorie pour identifier les thèmes
            $motsClefs = $this->getMotsClefsCategorie($categorie);
            
            foreach ($motsClefs as $theme => $mots) {
                foreach ($mots as $mot) {
                    if (str_contains($question, $mot)) {
                        $themes[$theme] = $theme;
                        break 2; // Passer à la question suivante
                    }
                }
            }
        }
        
        return array_values($themes);
    }

    /**
     * Retourne les mots-clés pour identifier les thèmes selon la catégorie
     */
    private function getMotsClefsCategorie(string $categorie): array
    {
        return match($categorie) {
            'stress' => [
                'Niveau général de stress' => ['stress', 'tension', 'niveau'],
                'Travail/professionnel' => ['travail', 'professionnel', 'boulot', 'emploi'],
                'Sommeil' => ['sommeil', 'dormir', 'insomnie', 'nuit'],
                'Relations sociales' => ['relation', 'famille', 'amis', 'social'],
                'Symptômes physiques' => ['physique', 'corps', 'maux', 'tension musculaire'],
                'Gestion du temps' => ['temps', 'organisation', 'surcharge'],
                'Anxiété' => ['anxieux', 'inquiet', 'peur', 'angoisse'],
                'Finances' => ['argent', 'financier', 'budget'],
            ],
            'depression' => [
                'Humeur générale' => ['humeur', 'sentiment', 'moral'],
                'Énergie/fatigue' => ['énergie', 'fatigue', 'épuisé'],
                'Activités/plaisir' => ['activité', 'plaisir', 'intérêt', 'hobby'],
                'Sommeil' => ['sommeil', 'dormir', 'nuit'],
                'Estime de soi' => ['estime', 'valeur', 'confiance'],
                'Concentration' => ['concentration', 'attention', 'focus'],
                'Isolement social' => ['isolement', 'seul', 'social'],
                'Pensées négatives' => ['pensée', 'négatif', 'sombre'],
            ],
            'iq' => [
                'Logique mathématique' => ['calcul', 'nombre', 'mathématique'],
                'Suites logiques' => ['suite', 'séquence', 'suivant'],
                'Compréhension verbale' => ['mot', 'synonyme', 'analogie'],
                'Raisonnement spatial' => ['forme', 'géométrie', 'spatial'],
                'Résolution de problèmes' => ['problème', 'solution', 'énigme'],
            ],
            default => []
        };
    }

    /**
     * Appel à l'API Groq
     */
    private function appelGroqApi(
        string $systemPrompt,
        string $userPrompt,
        float $temperature = 0.8,
        int $maxTokens = 500
    ): array {
        // Générer un seed unique basé sur le contenu du prompt pour éviter les répétitions
        $seed = crc32($userPrompt . microtime());
        
        $response = $this->httpClient->request('POST', self::GROQ_API_URL, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->groqApiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => self::DEFAULT_MODEL,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $systemPrompt
                    ],
                    [
                        'role' => 'user',
                        'content' => $userPrompt
                    ]
                ],
                'temperature' => $temperature,
                'max_tokens' => $maxTokens,
                'seed' => $seed, // Seed unique pour éviter les réponses identiques
                'response_format' => ['type' => 'json_object'] // Force JSON
            ],
        ]);

        $statusCode = $response->getStatusCode();
        
        if ($statusCode !== 200) {
            throw new \RuntimeException('Groq API error: ' . $statusCode);
        }

        return $response->toArray();
    }

    /**
     * Parse la réponse de Groq
     */
    private function parseResponse(array $response): array
    {
        $content = $response['choices'][0]['message']['content'] ?? '';
        
        // Parse le JSON de la réponse
        $data = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Erreur parsing JSON: ' . json_last_error_msg());
        }
        
        // Validation du format
        if (!isset($data['question']) || !isset($data['reponses'])) {
            throw new \RuntimeException('Format de réponse invalide');
        }
        
        if (count($data['reponses']) !== 4) {
            throw new \RuntimeException('Le nombre de réponses doit être exactement 4');
        }
        
        return [
            'texte' => $data['question'],
            'reponses' => $data['reponses']
        ];
    }

    /**
     * Question de secours si l'API échoue
     */
    private function getQuestionParDefaut(string $categorie, string $type): array
    {
        $questions = [
            'stress' => [
                'texte' => "Comment vous sentez-vous actuellement par rapport au stress ?",
                'reponses' => [
                    ['texte' => 'Très détendu(e), aucun stress', 'valeur' => 0],
                    ['texte' => 'Un peu stressé(e) mais gérable', 'valeur' => 1],
                    ['texte' => 'Assez stressé(e), cela m\'affecte', 'valeur' => 2],
                    ['texte' => 'Extrêmement stressé(e), débordé(e)', 'valeur' => 3],
                ]
            ],
            'depression' => [
                'texte' => "Comment décririez-vous votre humeur générale ces derniers jours ?",
                'reponses' => [
                    ['texte' => 'Bonne, je me sens bien', 'valeur' => 0],
                    ['texte' => 'Variable, avec hauts et bas', 'valeur' => 1],
                    ['texte' => 'Plutôt triste ou vide', 'valeur' => 2],
                    ['texte' => 'Très sombre, désespérée', 'valeur' => 3],
                ]
            ],
            'iq' => [
                'texte' => "Quelle suite logique : 2, 4, 8, 16, ... ?",
                'reponses' => [
                    ['texte' => '24', 'valeur' => 0],
                    ['texte' => '32', 'valeur' => 1],
                    ['texte' => '20', 'valeur' => 0],
                    ['texte' => '18', 'valeur' => 0],
                ]
            ],
        ];
        
        return $questions[$categorie] ?? $questions['stress'];
    }

    /**
     * Analyse par défaut si l'API échoue
     */
    private function getAnalyseParDefaut(string $categorie, int $score, int $nombreQuestions): string
    {
        $moyenneParQ = $nombreQuestions > 0 ? $score / $nombreQuestions : 0;
        
        $analyse = "Analyse du test de $categorie\n\n";
        $analyse .= "Score total : $score sur " . ($nombreQuestions * 3) . " points\n";
        $analyse .= "Moyenne par question : " . number_format($moyenneParQ, 2) . "\n\n";
        
        if ($moyenneParQ >= 2.5) {
            $analyse .= "⚠️ NIVEAU CRITIQUE\n";
            $analyse .= "Vos réponses indiquent une situation préoccupante nécessitant une attention immédiate.\n";
            $analyse .= "Recommandation : Consultez un professionnel de santé dès que possible.\n";
        } elseif ($moyenneParQ >= 1.5) {
            $analyse .= "⚠️ NIVEAU PRÉOCCUPANT\n";
            $analyse .= "Plusieurs de vos réponses indiquent des difficultés significatives.\n";
            $analyse .= "Recommandation : Envisagez de consulter un professionnel pour un suivi.\n";
        } elseif ($moyenneParQ >= 0.8) {
            $analyse .= "ℹ️ NIVEAU MODÉRÉ\n";
            $analyse .= "Vous présentez quelques signes à surveiller.\n";
            $analyse .= "Recommandation : Prenez soin de vous et surveillez l'évolution.\n";
        } else {
            $analyse .= "✅ NIVEAU FAIBLE\n";
            $analyse .= "Vos réponses indiquent un état globalement satisfaisant.\n";
            $analyse .= "Recommandation : Maintenez vos bonnes habitudes.\n";
        }
        
        return $analyse;
    }

    /**
     * Vérifie si l'API Groq est configurée
     */
    public function isConfigured(): bool
    {
        return !empty($this->groqApiKey) && $this->groqApiKey !== 'your_groq_api_key_here';
    }
}
