<?php

namespace App\Service;

use App\Repository\RendezVousRepository;
use App\Repository\UserRepository;
use Psr\Log\LoggerInterface;

class GeminiAssistant
{
    private RendezVousRepository $rdvRepo;
    private UserRepository $userRepo;
    private LoggerInterface $logger;

    public function __construct(string $apiKey, RendezVousRepository $rdvRepo, UserRepository $userRepo, LoggerInterface $logger)
    {
        $this->rdvRepo = $rdvRepo;
        $this->userRepo = $userRepo;
        $this->logger = $logger;
    }

    public function analyserDemande(string $question): array
    {
        $question = strtolower($question);
        
        // Extraire le nom du psychologue
        $psychologue = $this->extrairePsychologue($question);
        
        // Extraire la date
        $dateExtraite = $this->extraireDate($question);
        
        // Extraire l'heure approximative
        $periode = $this->extrairePeriode($question);
        
        // Extraire l'intention
        $intention = $this->extraireIntention($question);
        
        return [
            'psychologue' => $psychologue,
            'date_souhaitee' => $dateExtraite,
            'periode' => $periode,
            'intention' => $intention,
            'message' => $this->genererMessage($psychologue, $dateExtraite, $periode)
        ];
    }

    private function extrairePsychologue(string $question): ?string
    {
        $nomsPsy = ['med', 'narjes', 'bernard'];
        
        foreach ($nomsPsy as $nom) {
            if (strpos($question, $nom) !== false) {
                return $nom;
            }
        }
        return null;
    }

    private function extraireDate(string $question): ?string
    {
        $now = new \DateTime();
        
        // Mots-clés pour les jours relatifs
        if (strpos($question, 'demain') !== false) {
            return (clone $now)->modify('+1 day')->format('Y-m-d');
        }
        
        if (strpos($question, 'après-demain') !== false) {
            return (clone $now)->modify('+2 days')->format('Y-m-d');
        }
        
        // Jours de la semaine
        $jours = [
            'lundi' => 1,
            'mardi' => 2,
            'mercredi' => 3,
            'jeudi' => 4,
            'vendredi' => 5,
            'samedi' => 6,
            'dimanche' => 7
        ];
        
        foreach ($jours as $jour => $num) {
            if (strpos($question, $jour) !== false) {
                $today = (int) $now->format('N');
                $diff = ($num - $today + 7) % 7;
                if ($diff === 0) $diff = 7; // Prochain jour même jour
                
                return (clone $now)->modify("+$diff days")->format('Y-m-d');
            }
        }
        
        return null;
    }

    private function extrairePeriode(string $question): string
    {
        if (strpos($question, 'matin') !== false) {
            return 'matin';
        }
        if (strpos($question, 'après-midi') !== false) {
            return 'apres-midi';
        }
        if (strpos($question, 'soir') !== false) {
            return 'soir';
        }
        return 'toute';
    }

    private function extraireIntention(string $question): string
    {
        if (strpos($question, 'annuler') !== false) {
            return 'annulation';
        }
        if (strpos($question, 'modifier') !== false || strpos($question, 'changer') !== false) {
            return 'modification';
        }
        return 'nouveau';
    }

    private function genererMessage(?string $psychologue, ?string $date, string $periode): string
    {
        $message = "Je cherche";
        
        if ($psychologue) {
            $message .= " des créneaux avec $psychologue";
        } else {
            $message .= " des créneaux disponibles";
        }
        
        if ($date) {
            $dateObj = new \DateTime($date);
            $message .= " pour le " . $dateObj->format('d/m/Y');
        }
        
        if ($periode !== 'toute') {
            $message .= " ($periode)";
        }
        
        return $message;
    }

    public function suggererCreneaux(string $demande): array
    {
        $analyse = $this->analyserDemande($demande);
        
        if (isset($analyse['erreur'])) {
            return $analyse;
        }
        
        $creneaux = $this->rechercherCreneauxDisponibles(
            $analyse['psychologue'] ?? null,
            $analyse['date_souhaitee'] ?? null,
            $analyse['periode'] ?? 'toute'
        );
        
        return [
            'analyse' => $analyse,
            'creneaux' => $creneaux,
            'message' => $analyse['message']
        ];
    }

    private function rechercherCreneauxDisponibles(?string $psychologue, ?string $date, string $periode): array
    {
        $psychologues = ['med', 'narjes'];
        $creneauxDisponibles = [];
        $now = new \DateTime();
        
        // Définir la plage de dates à explorer
        $debut = $now;
        $fin = (clone $now)->modify('+7 days');
        
        // Si une date spécifique est demandée
        if ($date) {
            $dateObj = new \DateTime($date);
            $debut = clone $dateObj;
            $fin = clone $dateObj;
        }
        
        $psychologuesFiltres = $psychologues;
        if ($psychologue) {
            $psychologuesFiltres = array_filter($psychologues, function($p) use ($psychologue) {
                return strpos($p, strtolower($psychologue)) !== false;
            });
        }
        
        if (empty($psychologuesFiltres)) {
            return [];
        }
        
        // Générer les créneaux
        for ($jour = clone $debut; $jour <= $fin; $jour->modify('+1 day')) {
            // Créneaux selon la période
            $heures = [];
            if ($periode === 'matin') {
                $heures = [9, 11];
            } elseif ($periode === 'apres-midi') {
                $heures = [14, 16];
            } elseif ($periode === 'soir') {
                $heures = [18];
            } else {
                $heures = [9, 11, 14, 16, 18];
            }
            
            foreach ($heures as $h) {
                foreach ($psychologuesFiltres as $psy) {
                    $creneauxDisponibles[] = [
                        'id' => rand(100, 999),
                        'psychologue' => 'Dr. ' . ucfirst($psy),
                        'date' => $jour->format('Y-m-d'),
                        'heure' => sprintf('%02d:00', $h),
                        'patient' => 'Libre'
                    ];
                }
            }
        }
        
        // Trier par date/heure
        usort($creneauxDisponibles, function($a, $b) {
            return strcmp($a['date'] . $a['heure'], $b['date'] . $b['heure']);
        });
        
        return array_slice($creneauxDisponibles, 0, 8);
    }
}