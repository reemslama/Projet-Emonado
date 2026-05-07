<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DossierMedicalControllerTest extends WebTestCase
{
    public function testPatientDossierAccess(): void
    {
        $client = static::createClient();
        $client->request('GET', '/patient/dossier');
        $this->assertResponseRedirects('/login'); // Redirige si non connecté
    }
}