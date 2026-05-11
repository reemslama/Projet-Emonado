<?php
use App\Entity\TestPsyScene;
use Doctrine\ORM\EntityManagerInterface;

require __DIR__.'/../vendor/autoload.php';

$kernel = new App\Kernel('dev', true);
$kernel->boot();
$container = $kernel->getContainer();
$em = $container->get('doctrine.orm.entity_manager');

$scenes = $em->getRepository(TestPsyScene::class)->findAll();
foreach ($scenes as $s) {
    if ($s->getTitre() == 'looop' || $s->getTitre() == 'narjesse' || str_contains($s->getTitre(), 'narj')) {
        echo "Scene " . $s->getId() . " - " . $s->getTitre() . " - " . $s->getType() . "\n";
        foreach ($s->getReponses() as $r) {
            echo "  Reponse: id=" . $r->getId() . ", label=" . $r->getLabel() . ", emoji=" . $r->getEmoji() . ", imagePath=" . $r->getImagePath() . "\n";
        }
    }
}
