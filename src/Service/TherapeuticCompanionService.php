<?php

namespace App\Service;

use App\Entity\AnalyseEmotionnelle;

class TherapeuticCompanionService
{
    /**
     * @param AnalyseEmotionnelle[] $analyses Most recent first
     *
     * @return array{
     *   dominantEmotion:string,
     *   trajectory:string,
     *   empathy:string,
     *   hypothesis:string,
     *   smartQuestions:string[],
     *   dayPlan:array{matin:string,apresMidi:string,soir:string},
     *   nextSessionObjective:string,
     *   metrics:array{avgStress:float,avgWellBeing:float,sampleSize:int,sosCount:int}
     * }
     */
    public function buildPack(array $analyses): array
    {
        $sampleSize = count($analyses);
        if ($sampleSize === 0) {
            return [
                'dominantEmotion' => 'indeterminee',
                'trajectory' => 'insuffisant',
                'empathy' => 'Je ne dispose pas encore de donnees suffisantes pour comprendre le ressenti global.',
                'hypothesis' => 'Hypothese non disponible sans historique.',
                'smartQuestions' => [
                    'Quel evenement recent t a le plus impacte cette semaine ?',
                    'A quel moment de la journee te sens-tu le plus charge emotionnellement ?',
                    'Qu est-ce qui t a aide, meme un peu, a aller mieux recemment ?',
                ],
                'dayPlan' => [
                    'matin' => '2 minutes de respiration lente (4-4) au reveil.',
                    'apresMidi' => 'Noter une emotion dominante et son declencheur.',
                    'soir' => 'Ecrire 3 lignes de journal sur ce qui a ete supportable aujourd hui.',
                ],
                'nextSessionObjective' => 'Construire une base emotionnelle sur au moins 3 journaux.',
                'metrics' => [
                    'avgStress' => 0.0,
                    'avgWellBeing' => 0.0,
                    'sampleSize' => 0,
                    'sosCount' => 0,
                ],
            ];
        }

        $latest = $analyses[0];
        $dominantEmotion = $this->dominantEmotion($analyses);
        $trajectory = $this->trajectory($analyses);

        $avgStress = $this->avg($analyses, static fn (AnalyseEmotionnelle $a): float => (float) ($a->getNiveauStress() ?? 0));
        $avgWellBeing = $this->avg($analyses, static fn (AnalyseEmotionnelle $a): float => (float) ($a->getScoreBienEtre() ?? 0));
        $sosCount = 0;
        foreach ($analyses as $a) {
            if ($a->getJournal()?->getHumeur() === 'SOS') {
                $sosCount++;
            }
        }

        $empathy = $this->buildEmpathy($dominantEmotion, $trajectory, (float) $latest->getNiveauStress(), (float) $latest->getScoreBienEtre());
        $hypothesis = $this->buildHypothesis($dominantEmotion, $trajectory, $sosCount);
        $questions = $this->buildQuestions($dominantEmotion, $trajectory);
        $dayPlan = $this->buildDayPlan($dominantEmotion, $trajectory, (float) $latest->getNiveauStress());
        $objective = $this->buildObjective($dominantEmotion, $trajectory);

        return [
            'dominantEmotion' => $dominantEmotion,
            'trajectory' => $trajectory,
            'empathy' => $empathy,
            'hypothesis' => $hypothesis,
            'smartQuestions' => $questions,
            'dayPlan' => $dayPlan,
            'nextSessionObjective' => $objective,
            'metrics' => [
                'avgStress' => round($avgStress, 2),
                'avgWellBeing' => round($avgWellBeing, 2),
                'sampleSize' => $sampleSize,
                'sosCount' => $sosCount,
            ],
        ];
    }

    /** @param AnalyseEmotionnelle[] $analyses */
    private function dominantEmotion(array $analyses): string
    {
        $counts = [];
        foreach ($analyses as $a) {
            $emotion = (string) ($a->getEmotionPrincipale() ?? 'neutre');
            $counts[$emotion] = ($counts[$emotion] ?? 0) + 1;
        }
        arsort($counts);

        return (string) array_key_first($counts);
    }

    /** @param AnalyseEmotionnelle[] $analyses */
    private function trajectory(array $analyses): string
    {
        if (count($analyses) < 4) {
            return 'stable';
        }

        $recent = array_slice($analyses, 0, 3);
        $older = array_slice($analyses, 3);

        $recentStress = $this->avg($recent, static fn (AnalyseEmotionnelle $a): float => (float) ($a->getNiveauStress() ?? 0));
        $olderStress = $this->avg($older, static fn (AnalyseEmotionnelle $a): float => (float) ($a->getNiveauStress() ?? 0));

        $recentWell = $this->avg($recent, static fn (AnalyseEmotionnelle $a): float => (float) ($a->getScoreBienEtre() ?? 0));
        $olderWell = $this->avg($older, static fn (AnalyseEmotionnelle $a): float => (float) ($a->getScoreBienEtre() ?? 0));

        if ($recentStress - $olderStress >= 1.2 || $olderWell - $recentWell >= 10.0) {
            return 'degradation';
        }

        if ($olderStress - $recentStress >= 1.2 || $recentWell - $olderWell >= 10.0) {
            return 'amelioration';
        }

        return 'stable';
    }

    private function buildEmpathy(string $dominantEmotion, string $trajectory, float $stress, float $wellBeing): string
    {
        $prefix = match ($dominantEmotion) {
            'tristesse' => 'Je vois une tristesse recurrente dans les dernieres expressions.',
            'colere' => 'Je vois de la tension et de la frustration qui reviennent souvent.',
            'peur' => 'Je vois une insecurite emotionnelle qui prend de la place.',
            'joie' => 'Je vois une capacite a retrouver des moments de mieux-etre.',
            default => 'Je vois un etat emotionnel fluctuant mais exprimable.',
        };

        $suffix = match ($trajectory) {
            'degradation' => 'La tendance recente semble plus chargee qu avant.',
            'amelioration' => 'La tendance recente montre des signes d amelioration.',
            default => 'La tendance recente reste relativement stable.',
        };

        return $prefix . ' ' . $suffix . sprintf(' (Stress actuel %.1f/10, bien-etre %.1f/100).', $stress, $wellBeing);
    }

    private function buildHypothesis(string $dominantEmotion, string $trajectory, int $sosCount): string
    {
        $core = match ($dominantEmotion) {
            'tristesse' => 'Possible epuisement emotionnel avec retrait progressif.',
            'colere' => 'Possible surcharge cognitive avec difficultes de regulation.',
            'peur' => 'Possible anticipation anxieuse et hypervigilance.',
            'joie' => 'Presence de ressources adaptatives exploitables en therapie.',
            default => 'Etat mixte necessitant clarification contextuelle.',
        };

        $trend = $trajectory === 'degradation'
            ? ' Les indicateurs recents suggerent une aggravation a surveiller de pres.'
            : ($trajectory === 'amelioration'
                ? ' Les indicateurs recents montrent une evolution encourageante.'
                : ' Les indicateurs recents sont stables.');

        $sos = $sosCount >= 2 ? ' Plusieurs episodes SOS renforcent la priorite clinique.' : '';

        return trim($core . $trend . $sos);
    }

    /** @return string[] */
    private function buildQuestions(string $dominantEmotion, string $trajectory): array
    {
        $base = [
            'Quel moment recent t a fait sentir cette emotion le plus fortement ?',
            'Qu est-ce qui a legerement aide, meme a 10 %, a mieux tenir ?',
        ];

        $third = match ($dominantEmotion) {
            'tristesse' => 'Quand la tristesse monte, quelle pensee automatique apparait en premier ?',
            'colere' => 'Juste avant la colere, quel signal corporel arrives-tu a reperer ?',
            'peur' => 'Quelle prediction negative revient le plus souvent dans ta tete ?',
            'joie' => 'Quelles conditions ont favorise ces moments de mieux-etre ?',
            default => 'Quelle emotion secondaire se cache derriere ce que tu ressens en premier ?',
        };

        $fourth = $trajectory === 'degradation'
            ? 'Qu est-ce qui a change dans ta routine cette semaine par rapport a avant ?'
            : 'Quelle habitude utile peux-tu maintenir les 3 prochains jours ?';

        $base[] = $third;
        $base[] = $fourth;

        return $base;
    }

    /** @return array{matin:string,apresMidi:string,soir:string} */
    private function buildDayPlan(string $dominantEmotion, string $trajectory, float $stress): array
    {
        $matin = $stress >= 6.0
            ? 'Respiration 4-6 pendant 3 minutes au reveil + 1 intention realiste pour la journee.'
            : '2 minutes de recentrage respiratoire + plan rapide des priorites du jour.';

        $apresMidi = $dominantEmotion === 'colere'
            ? 'Pause de desescalade de 90 secondes avant toute reponse impulsive.'
            : 'Noter en une phrase le declencheur emotionnel principal de la journee.';

        $soir = $trajectory === 'degradation'
            ? 'Journal en 3 colonnes: fait, emotion, besoin non satisfait.'
            : 'Journal bref: 1 difficulte geree + 1 ressource utilisee.';

        return [
            'matin' => $matin,
            'apresMidi' => $apresMidi,
            'soir' => $soir,
        ];
    }

    private function buildObjective(string $dominantEmotion, string $trajectory): string
    {
        if ($trajectory === 'degradation') {
            return 'Identifier les declencheurs prioritaires et stabiliser la regulation emotionnelle de base.';
        }

        if ($dominantEmotion === 'joie' && $trajectory === 'amelioration') {
            return 'Consolider les facteurs protecteurs pour maintenir les progres.';
        }

        return 'Transformer les observations du journal en strategies concretes de coping.';
    }

    /**
     * @param AnalyseEmotionnelle[] $rows
     * @param callable(AnalyseEmotionnelle):float $getter
     */
    private function avg(array $rows, callable $getter): float
    {
        if ($rows === []) {
            return 0.0;
        }

        $sum = 0.0;
        foreach ($rows as $row) {
            $sum += $getter($row);
        }

        return $sum / count($rows);
    }
}
