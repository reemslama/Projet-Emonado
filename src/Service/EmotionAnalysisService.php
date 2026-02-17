<?php

namespace App\Service;

class EmotionAnalysisService
{
    /**
     * Analyse "IA" locale, sans dépendance externe,
     * basée sur des mots-clés pondérés dans le texte.
     *
     * On évite ainsi toute erreur liée à la configuration d'une API distante.
     */
    public function analyze(string $contenu): array
    {
        return $this->fallbackAnalysis($contenu);
    }

    /**
     * Analyse locale améliorée du texte.
     *
     * - Normalisation en minuscules sans accents
     * - Comptage pondéré des mots liés au stress / bien-être
     * - Prise en compte de l'intensité (ex: "très", "complètement")
     * - Clamp du stress entre 0 et 10, bien-être entre 0 et 100
     */
    private function fallbackAnalysis(string $contenu): array
    {
        $normalized = $this->normalizeText($contenu);

        // ----------------------------
        // 1) Détection de l'intensité
        // ----------------------------
        $intensityWords = [
            'tres', 'tellement', 'vraiment', 'completement',
            'totalement', 'extremement', 'trop', 'fortement',
            'plus en plus', 'epuise', 'epuisee',
        ];

        $intensityScore = 0.0;
        foreach ($intensityWords as $word) {
            $intensityScore += substr_count($normalized, $word);
        }
        // Facteur multiplicateur entre 1.0 et 2.0
        $intensityFactor = 1.0 + min($intensityScore * 0.2, 1.0);

        // ---------------------------------
        // 2) Stress et anxiété (0 à 10)
        // ---------------------------------
        $stressKeywords = [
            'stress'    => 3.0,
            'stresse'   => 3.0,
            'angoisse'  => 3.0,
            'anxieux'   => 3.0,
            'anxiete'   => 3.0,
            'peur'      => 2.5,
            'panique'   => 3.0,
            'inquiet'   => 2.0,
            'pression'  => 2.0,
            'deborde'   => 2.0,
            'epuise'    => 2.0,
            'epuisee'   => 2.0,
            'fatigue'   => 1.8,
            'fatiguee'  => 1.8,
            'tendu'     => 1.5,
        ];

        $calmKeywords = [
            'calme'     => 2.0,
            'detendu'   => 2.0,
            'apaise'    => 2.0,
            'soulag'    => 2.0,
            'relaxe'    => 2.0,
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
                $stressScore -= $count * $weight; // le calme réduit le stress
            }
        }

        // Normalisation sur l'échelle 0–10
        // On considère qu'un stressScore brut de 0–25 couvre nos cas courants
        $stressScore = max(0.0, min(25.0, $stressScore));
        $niveauStress = (int) round(($stressScore / 25.0) * 10.0);

        // ---------------------------------
        // 3) Score de bien-être (0 à 100)
        // ---------------------------------
        $positiveKeywords = [
            'heureux'       => 4.0,
            'heureuse'      => 4.0,
            'joie'          => 4.0,
            'content'       => 3.0,
            'contente'      => 3.0,
            'reconnaissant' => 3.0,
            'reconnaissante'=> 3.0,
            'calme'         => 2.5,
            'apaise'        => 2.5,
            'soulag'        => 2.5,
            'bien'          => 2.0,
        ];

        $negativeKeywords = [
            'triste'    => 4.0,
            'mal'       => 2.5,
            'peur'      => 3.0,
            'angoisse'  => 3.5,
            'anxieux'   => 3.5,
            'anxiete'   => 3.5,
            'fatigue'   => 3.0,
            'fatiguee'  => 3.0,
            'epuise'    => 3.5,
            'epuisee'   => 3.5,
            'seul'      => 3.0,
            'seule'     => 3.0,
            'deprime'   => 4.0,
            'vide'      => 3.0,
        ];

        // Point de départ "neutre"
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

        // On tient compte du stress élevé pour baisser encore un peu le bien‑être
        $wellBeingScore -= $niveauStress * 2.0;

        // Clamp 0–100
        $wellBeingScore = max(0.0, min(100.0, $wellBeingScore));
        $scoreBienEtre = (int) round($wellBeingScore);

        // ---------------------------------
        // 4) Émotion principale
        // ---------------------------------
        $emotionScores = [
            'joie'      => 0.0,
            'tristesse' => 0.0,
            'colere'    => 0.0,
            'peur'      => 0.0,
        ];

        $joyWords = ['heureux', 'heureuse', 'joie', 'content', 'contente', 'soulag', 'reconnaissan'];
        foreach ($joyWords as $word) {
            $emotionScores['joie'] += substr_count($normalized, $word);
        }

        $sadWords = ['triste', 'seul', 'seule', 'vide', 'deprime'];
        foreach ($sadWords as $word) {
            $emotionScores['tristesse'] += substr_count($normalized, $word);
        }

        $angerWords = ['colere', 'enerve', 'enervee', 'frustre', 'frustree'];
        foreach ($angerWords as $word) {
            $emotionScores['colere'] += substr_count($normalized, $word);
        }

        $fearWords = ['peur', 'angoisse', 'anxieux', 'anxiete', 'inquiet'];
        foreach ($fearWords as $word) {
            $emotionScores['peur'] += substr_count($normalized, $word);
        }

        $maxEmotionScore = max($emotionScores);
        if ($maxEmotionScore <= 0.0) {
            $emotion = 'neutre';
        } else {
            // Prendre la première émotion qui a le score max
            $dominant = array_keys(array_filter($emotionScores, fn ($v) => $v === $maxEmotionScore))[0];
            $emotion = $dominant;
        }

        return [
            'emotionPrincipale' => $emotion,
            'niveauStress'      => $niveauStress,
            'scoreBienEtre'     => $scoreBienEtre,
            'resumeIA'          => 'Analyse locale basée sur la densité et l\'intensité des mots émotionnels.',
        ];
    }

    /**
     * Normalise le texte : minuscules + suppression des accents
     * pour permettre une recherche insensible à la casse et aux accents.
     */
    private function normalizeText(string $text): string
    {
        $text = mb_strtolower($text);

        // Remplacement manuel des accents les plus courants (évite toute dépendance externe)
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

