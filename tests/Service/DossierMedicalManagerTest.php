<?php
namespace App\Tests\Service;

use App\Entity\DossierMedical;
use App\Entity\User;
use App\Service\DossierMedicalManager;
use PHPUnit\Framework\TestCase;

class DossierMedicalManagerTest extends TestCase
{
    private function buildValid(): DossierMedical
    {
        $patient = (new User())
            ->setEmail('patient@test.com')
            ->setNom('Patient')
            ->setPassword('secret');

        $dossier = new DossierMedical();
        $dossier
            ->setHistoriqueMedical(str_repeat('h', 12))
            ->setNotesPsychologiques(str_repeat('n', 12))
            ->setPatient($patient);

        return $dossier;
    }

    public function testValidDossier(): void
    {
        $dossier = $this->buildValid();
        $manager = new DossierMedicalManager();
        $this->assertTrue($manager->validate($dossier));
    }

    public function testHistoriqueManquant(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $dossier = $this->buildValid()->setHistoriqueMedical(null);
        (new DossierMedicalManager())->validate($dossier);
    }

    public function testHistoriqueTropCourt(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $dossier = $this->buildValid()->setHistoriqueMedical('court');
        (new DossierMedicalManager())->validate($dossier);
    }

    public function testNotesManquantes(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $dossier = $this->buildValid()->setNotesPsychologiques(null);
        (new DossierMedicalManager())->validate($dossier);
    }

    public function testPatientManquant(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $dossier = $this->buildValid()->setPatient(null);
        (new DossierMedicalManager())->validate($dossier);
    }
}