<?php

namespace App\Service;

use App\Entity\AnalyseEmotionnelle;

class CoachSuggestionService
{
    /**
     * @return array{
     *   now:string,
     *   today:string,
     *   tonight:string,
     *   question:string,
     *   reformulation:string
     * }
     */
    public function buildFromAnalyse(AnalyseEmotionnelle $analyse): array
    {
        $emotion = (string) ($analyse->getEmotionPrincipale() ?? 'neutre');
        $stress = (int) ($analyse->getNiveauStress() ?? 0);
        $wellBeing = (int) ($analyse->getScoreBienEtre() ?? 50);

        $reformulation = sprintf(
            'Tu sembles traverser une phase plutot %s, avec un stress a %d/10 et un bien-etre a %d/100.',
            $emotion,
            $stress,
            $wellBeing
        );

        $now = $stress >= 7
            ? '2 minutes: respiration lente 4-6 + epaules relachees.'
            : '2 minutes: pause consciente et recentrage sur ta respiration.';

        $today = match ($emotion) {
            'tristesse' => 'Planifie une interaction de soutien (appel/message) et note comment tu te sens apres.',
            'colere' => 'Avant chaque reaction forte, applique la regle STOP: stop, souffle, observe, puis reponds.',
            'peur' => 'Ecris la pensee qui t inquiete puis une version plus realiste en face.',
            'joie' => 'Renforce ce qui t a aide aujourd hui pour reproduire cet etat demain.',
            default => 'Note 1 declencheur principal et 1 ressource utile que tu peux reutiliser.',
        };

        $tonight = $wellBeing < 45
            ? 'Ce soir, ecris 3 lignes: ce qui etait difficile, ce que tu as reussi a gerer, et un besoin pour demain.'
            : 'Ce soir, ecris 3 lignes: un fait positif, une difficulte, et une intention simple pour demain.';

        $question = match ($emotion) {
            'tristesse' => 'Quel besoin important est reste non satisfait aujourd hui ?',
            'colere' => 'Quel signal precoce annonce ta montee de colere ?',
            'peur' => 'Quelle prediction negative revient le plus souvent ?',
            'joie' => 'Qu est-ce qui a concretement favorise ce mieux-etre ?',
            default => 'Quelle emotion est apparue en premier, et laquelle venait juste derriere ?',
        };

        return [
            'now' => $now,
            'today' => $today,
            'tonight' => $tonight,
            'question' => $question,
            'reformulation' => $reformulation,
        ];
    }
}

