<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class EmotionAnalysisService
{
    public function __construct(private readonly HttpClientInterface $httpClient)
    {
    }

    public function analyze(string $contenu): array
    {
        $normalized = $this->normalizeText($contenu);
        if ($this->hasCriticalSignal($normalized)) {
            return $this->criticalSignalAnalysis();
        }

        $llmResult = $this->llmAnalysis($contenu);
        if ($llmResult !== null) {
            return $llmResult;
        }

        return $this->fallbackAnalysis($contenu);
    }

    public function determineMood(string $contenu, array $analysis): string
    {
        $normalized = $this->normalizeText($contenu);
        if ($this->hasCriticalSignal($normalized)) {
            return 'SOS';
        }

        $emotion = (string) ($analysis['emotionPrincipale'] ?? 'neutre');
        return match ($emotion) {
            'joie' => 'heureux',
            'colere' => 'en colere',
            'tristesse', 'peur' => 'SOS',
            default => ((int) ($analysis['niveauStress'] ?? 0) >= 7 ? 'SOS' : 'calme'),
        };
    }

    private function llmAnalysis(string $contenu): ?array
    {
        $apiKey = trim((string) ($_ENV['OPENAI_API_KEY'] ?? ''));
        if ($apiKey === '') {
            return null;
        }

        $model = trim((string) ($_ENV['OPENAI_EMOTION_MODEL'] ?? 'gpt-4o-mini'));
        if ($model === '') {
            $model = 'gpt-4o-mini';
        }

        $systemPrompt = 'Tu es un analyste emotionnel clinique. Reponds strictement en JSON valide avec les cles: emotionPrincipale, niveauStress, scoreBienEtre, resumeIA. emotionPrincipale doit etre une de: joie, tristesse, colere, peur, neutre. niveauStress entier 0..10. scoreBienEtre entier 0..100. resumeIA concis en francais simple, sans markdown, max 220 caracteres.';
        $userPrompt = "Texte journal patient:\n" . $contenu;

        try {
            $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $model,
                    'temperature' => 0.2,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userPrompt],
                    ],
                ],
                'timeout' => 20,
            ]);

            $payload = $response->toArray(false);
            $content = (string) ($payload['choices'][0]['message']['content'] ?? '');
            if ($content === '') {
                return null;
            }

            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($decoded)) {
                return null;
            }

            return $this->sanitizeAnalysisFromModel($decoded);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $raw
     *
     * @return array{emotionPrincipale:string,niveauStress:int,scoreBienEtre:int,resumeIA:string}
     */
    private function sanitizeAnalysisFromModel(array $raw): array
    {
        $emotion = mb_strtolower(trim((string) ($raw['emotionPrincipale'] ?? 'neutre')));
        $allowed = ['joie', 'tristesse', 'colere', 'peur', 'neutre'];
        if (!in_array($emotion, $allowed, true)) {
            $emotion = 'neutre';
        }

        $stress = max(0, min(10, (int) ($raw['niveauStress'] ?? 0)));
        $wellBeing = max(0, min(100, (int) ($raw['scoreBienEtre'] ?? 50)));

        $resume = trim((string) ($raw['resumeIA'] ?? 'Analyse emotionnelle generee par IA.'));
        if ($resume === '') {
            $resume = 'Analyse emotionnelle generee par IA.';
        }
        if (mb_strlen($resume) > 260) {
            $resume = mb_substr($resume, 0, 257) . '...';
        }

        return [
            'emotionPrincipale' => $emotion,
            'niveauStress' => $stress,
            'scoreBienEtre' => $wellBeing,
            'resumeIA' => $resume,
        ];
    }

    private function criticalSignalAnalysis(): array
    {
        return [
            'emotionPrincipale' => 'tristesse',
            'niveauStress' => 10,
            'scoreBienEtre' => 5,
            'resumeIA' => 'Signal de detresse majeur detecte dans le discours. Prioriser une prise en charge humaine immediate.',
        ];
    }

    private function fallbackAnalysis(string $contenu): array
    {
        $normalized = $this->normalizeText($contenu);

        if ($this->hasCriticalSignal($normalized)) {
            return $this->criticalSignalAnalysis();
        }

        $intensityWords = [
            'tres', 'tellement', 'vraiment', 'completement',
            'totalement', 'extremement', 'trop', 'fortement',
            'plus en plus', 'epuise', 'epuisee',
        ];

        $intensityScore = 0.0;
        foreach ($intensityWords as $word) {
            $intensityScore += substr_count($normalized, $word);
        }
        $intensityFactor = 1.0 + min($intensityScore * 0.2, 1.0);

        $stressKeywords = [
            'stress' => 3.0,
            'stresse' => 3.0,
            'angoisse' => 3.0,
            'anxieux' => 3.0,
            'anxiete' => 3.0,
            'peur' => 2.5,
            'panique' => 3.0,
            'inquiet' => 2.0,
            'pression' => 2.0,
            'deborde' => 2.0,
            'epuise' => 2.0,
            'epuisee' => 2.0,
            'fatigue' => 1.8,
            'fatiguee' => 1.8,
            'tendu' => 1.5,
        ];

        $calmKeywords = [
            'calme' => 2.0,
            'detendu' => 2.0,
            'apaise' => 2.0,
            'soulag' => 2.0,
            'relaxe' => 2.0,
        ];

        $stressScore = 0.0;
        foreach ($stressKeywords as $word => $weight) {
            $count = substr_count($normalized, $word);
            if ($count > 0) {
                $stressScore += $count * $weight * $intensityFactor;
            }
        }

        foreach ($calmKeywords as $word => $weight) {
            $count = substr_count($normalized, $word);
            if ($count > 0) {
                $stressScore -= $count * $weight;
            }
        }

        $stressScore = max(0.0, min(25.0, $stressScore));
        $niveauStress = (int) round(($stressScore / 25.0) * 10.0);

        $positiveKeywords = [
            'heureux' => 4.0,
            'heureuse' => 4.0,
            'joie' => 4.0,
            'content' => 3.0,
            'contente' => 3.0,
            'reconnaissant' => 3.0,
            'reconnaissante' => 3.0,
            'calme' => 2.5,
            'apaise' => 2.5,
            'soulag' => 2.5,
            'bien' => 2.0,
        ];

        $negativeKeywords = [
            'triste' => 4.0,
            'mal' => 2.5,
            'peur' => 3.0,
            'angoisse' => 3.5,
            'anxieux' => 3.5,
            'anxiete' => 3.5,
            'fatigue' => 3.0,
            'fatiguee' => 3.0,
            'epuise' => 3.5,
            'epuisee' => 3.5,
            'seul' => 3.0,
            'seule' => 3.0,
            'deprime' => 4.0,
            'vide' => 3.0,
        ];

        $wellBeingScore = 60.0;

        foreach ($positiveKeywords as $word => $weight) {
            $count = substr_count($normalized, $word);
            if ($count > 0) {
                $wellBeingScore += $count * $weight;
            }
        }

        foreach ($negativeKeywords as $word => $weight) {
            $count = substr_count($normalized, $word);
            if ($count > 0) {
                $wellBeingScore -= $count * $weight * $intensityFactor;
            }
        }

        $wellBeingScore -= $niveauStress * 2.0;
        $wellBeingScore = max(0.0, min(100.0, $wellBeingScore));
        $scoreBienEtre = (int) round($wellBeingScore);

        $emotionScores = [
            'joie' => 0.0,
            'tristesse' => 0.0,
            'colere' => 0.0,
            'peur' => 0.0,
        ];

        foreach (['heureux', 'heureuse', 'joie', 'content', 'contente', 'soulag', 'reconnaissan'] as $word) {
            $emotionScores['joie'] += substr_count($normalized, $word);
        }
        foreach (['triste', 'seul', 'seule', 'vide', 'deprime'] as $word) {
            $emotionScores['tristesse'] += substr_count($normalized, $word);
        }
        foreach (['colere', 'enerve', 'enervee', 'frustre', 'frustree'] as $word) {
            $emotionScores['colere'] += substr_count($normalized, $word);
        }
        foreach (['peur', 'angoisse', 'anxieux', 'anxiete', 'inquiet'] as $word) {
            $emotionScores['peur'] += substr_count($normalized, $word);
        }

        $maxEmotionScore = max($emotionScores);
        $emotion = $maxEmotionScore <= 0.0
            ? 'neutre'
            : (string) array_keys(array_filter($emotionScores, static fn ($v) => $v === $maxEmotionScore))[0];

        $resumeIA = $this->buildResume($emotion, $niveauStress, $scoreBienEtre);

        return [
            'emotionPrincipale' => $emotion,
            'niveauStress' => $niveauStress,
            'scoreBienEtre' => $scoreBienEtre,
            'resumeIA' => $resumeIA,
        ];
    }

    private function buildResume(string $emotion, int $stress, int $wellBeing): string
    {
        $moodText = match ($emotion) {
            'joie' => 'un registre emotionnel plutot positif',
            'tristesse' => 'une tonalite de tristesse recurrente',
            'colere' => 'une tension de type colere/frustration',
            'peur' => 'un axe anxieux et apprehensif',
            default => 'un etat emotionnel mixte',
        };

        $riskText = $stress >= 7
            ? 'Le stress est eleve et demande une action rapide de regulation.'
            : ($stress >= 4
                ? 'Le stress est modere et reste a surveiller.'
                : 'Le stress reste contenu.');

        $wellBeingText = $wellBeing < 45
            ? 'Le bien-etre global est bas actuellement.'
            : ($wellBeing < 70
                ? 'Le bien-etre est moyen avec marge d amelioration.'
                : 'Le bien-etre global est satisfaisant.');

        return sprintf(
            'L analyse detecte %s. %s %s',
            $moodText,
            $riskText,
            $wellBeingText
        );
    }

    private function hasCriticalSignal(string $normalized): bool
    {
        $patterns = [
            '/\\b(je\\s+veux|envie\\s+de)\\s+mour/i',
            '/\\b(me\\s+)?suicid/i',
            '/\\b(me\\s+)?tu(er|e)\\b/i',
            '/\\ben\\s+finir\\b/i',
            '/\\bplus\\s+envie\\s+de\\s+vivre\\b/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $normalized) === 1) {
                return true;
            }
        }

        return false;
    }

    private function normalizeText(string $text): string
    {
        $text = mb_strtolower($text);

        $replacements = [
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ä' => 'a',
            'ç' => 'c',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'î' => 'i', 'ï' => 'i', 'ì' => 'i', 'í' => 'i',
            'ô' => 'o', 'ö' => 'o', 'ò' => 'o', 'ó' => 'o',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
            'ÿ' => 'y',
        ];

        return strtr($text, $replacements);
    }
}
