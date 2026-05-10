<?php

use App\Entity\Disponibilite;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

require __DIR__.'/../vendor/autoload.php';

$kernel = new App\Kernel('dev', true);
$kernel->boot();
$container = $kernel->getContainer();
$em = $container->get('doctrine.orm.entity_manager');

$psy = $em->getRepository(User::class)->find(2); // Dupontt Jean

if (!$psy) {
    echo "Psychologue not found\n";
    exit;
}

$date = new \DateTime('today');
$date->modify('+1 day');

for ($i = 0; $i < 5; $i++) {
    $d = new Disponibilite();
    $d->setPsychologue($psy);
    $d->setDate(clone $date);
    $d->setHeureDebut(new \DateTime('09:00'));
    $d->setHeureFin(new \DateTime('10:00'));
    $d->setLibre(true);
    $em->persist($d);
    
    $d2 = new Disponibilite();
    $d2->setPsychologue($psy);
    $d2->setDate(clone $date);
    $d2->setHeureDebut(new \DateTime('14:00'));
    $d2->setHeureFin(new \DateTime('15:00'));
    $d2->setLibre(true);
    $em->persist($d2);
    
    $date->modify('+1 day');
}

$em->flush();
echo "Added availabilities for Dupontt Jean starting tomorrow\n";
