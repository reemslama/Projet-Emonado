<?php
namespace App\Service;

use App\Entity\DossierMedical;

class DossierMedicalManager
{
    public function validate(DossierMedical $dossier): bool
    {
        $historique = $dossier->getHistoriqueMedical();
        if (empty($historique) || mb_strlen(trim($historique)) < 10) {
            throw new \InvalidArgumentException("L'historique médical doit contenir au moins 10 caractères");
        }

        $notes = $dossier->getNotesPsychologiques();
        if (empty($notes) || mb_strlen(trim($notes)) < 10) {
            throw new \InvalidArgumentException('Les notes psychologiques doivent contenir au moins 10 caractères');
        }

        if (null === $dossier->getPatient()) {
            throw new \InvalidArgumentException('Le patient est obligatoire');
        }

        return true;
    }
}