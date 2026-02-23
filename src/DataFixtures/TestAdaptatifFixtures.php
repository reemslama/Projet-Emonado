<?php

namespace App\DataFixtures;

use App\Entity\TestAdaptatif;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class TestAdaptatifFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Récupérer des utilisateurs patients
        $patients = $manager->getRepository(User::class)->findAll();
        
        if (empty($patients)) {
            echo "⚠️ Aucun patient trouvé. Créez d'abord des utilisateurs.\n";
            return;
        }

        $patient = $patients[0]; // Premier utilisateur

        // Test 1 - Stress (Score élevé)
        $test1 = new TestAdaptatif();
        $test1->setPatient($patient)
            ->setCategorie('stress')
            ->setTermine(true)
            ->setDateDebut(new \DateTimeImmutable('-7 days'))
            ->setDateFin(new \DateTimeImmutable('-7 days +15 minutes'));

        // Ajouter des questions/réponses
        $test1->ajouterQuestionReponse(
            'Comment gérez-vous votre stress quotidien ?',
            'Très difficilement',
            3
        );
        $test1->ajouterQuestionReponse(
            'Avez-vous des troubles du sommeil ?',
            'Oui, fréquemment',
            3
        );
        $test1->ajouterQuestionReponse(
            'Ressentez-vous de l\'anxiété au travail ?',
            'Constamment',
            4
        );
        $test1->ajouterQuestionReponse(
            'Avez-vous des tensions musculaires ?',
            'Très souvent',
            3
        );
        $test1->ajouterQuestionReponse(
            'Comment est votre concentration ?',
            'Très difficile de me concentrer',
            3
        );

        $test1->setAnalyse("L'analyse de ce test de stress révèle un niveau élevé d'anxiété et de tension. Les réponses indiquent des difficultés importantes dans la gestion du stress quotidien, avec des impacts sur le sommeil et la concentration. Il est recommandé de consulter un professionnel et de mettre en place des techniques de relaxation comme la méditation, la respiration profonde ou le yoga. Un suivi régulier permettra d'évaluer l'évolution et d'ajuster les stratégies d'adaptation.");

        $manager->persist($test1);

        // Test 2 - Dépression (Score moyen)
        $test2 = new TestAdaptatif();
        $test2->setPatient($patient)
            ->setCategorie('depression')
            ->setTermine(true)
            ->setDateDebut(new \DateTimeImmutable('-5 days'))
            ->setDateFin(new \DateTimeImmutable('-5 days +20 minutes'));

        $test2->ajouterQuestionReponse(
            'Comment vous sentez-vous ces derniers jours ?',
            'Plutôt mal',
            2
        );
        $test2->ajouterQuestionReponse(
            'Avez-vous perdu de l\'intérêt pour vos activités ?',
            'Partiellement',
            2
        );
        $test2->ajouterQuestionReponse(
            'Comment est votre appétit ?',
            'Diminué',
            2
        );
        $test2->ajouterQuestionReponse(
            'Vous sentez-vous fatigué(e) ?',
            'Assez souvent',
            2
        );
        $test2->ajouterQuestionReponse(
            'Avez-vous des pensées négatives ?',
            'Parfois',
            2
        );
        $test2->ajouterQuestionReponse(
            'Comment voyez-vous l\'avenir ?',
            'Avec inquiétude',
            2
        );

        $test2->setAnalyse("Ce test révèle des symptômes dépressifs modérés. Les réponses suggèrent une baisse de moral avec des impacts sur l'appétit, l'énergie et la vision de l'avenir. Il est important de rester vigilant et de ne pas laisser ces symptômes s'aggraver. Une thérapie cognitivo-comportementale pourrait être bénéfique, associée à des activités physiques régulières et un soutien social.");

        $manager->persist($test2);

        // Test 3 - IQ (Score bon)
        $test3 = new TestAdaptatif();
        $test3->setPatient($patient)
            ->setCategorie('iq')
            ->setTermine(true)
            ->setDateDebut(new \DateTimeImmutable('-3 days'))
            ->setDateFin(new \DateTimeImmutable('-3 days +30 minutes'));

        $test3->ajouterQuestionReponse(
            'Quel nombre vient après 2, 4, 8, 16 ?',
            '32',
            1
        );
        $test3->ajouterQuestionReponse(
            'Complétez la suite : A, C, E, G, ?',
            'I',
            1
        );
        $test3->ajouterQuestionReponse(
            'Si tous les A sont B et certains B sont C, alors...',
            'Certains A peuvent être C',
            1
        );
        $test3->ajouterQuestionReponse(
            'Quelle forme complète le motif ?',
            'Triangle',
            1
        );
        $test3->ajouterQuestionReponse(
            'Résolvez : 15 + 7 × 3 = ?',
            '36',
            1
        );
        $test3->ajouterQuestionReponse(
            'Quel mot n\'appartient pas au groupe ?',
            'Carotte (les autres sont des fruits)',
            1
        );
        $test3->ajouterQuestionReponse(
            'Analogie : Chien est à chiot comme chat est à ?',
            'Chaton',
            1
        );

        $test3->setAnalyse("Les résultats de ce test cognitif démontrent de bonnes capacités de raisonnement logique, de reconnaissance de patterns et de résolution de problèmes. Les scores indiquent un niveau intellectuel dans la moyenne supérieure avec des aptitudes particulièrement développées en logique mathématique et en raisonnement verbal.");

        $manager->persist($test3);

        // Test 4 - Anxiété (Score faible - bon résultat)
        $test4 = new TestAdaptatif();
        $test4->setPatient($patient)
            ->setCategorie('anxiete')
            ->setTermine(true)
            ->setDateDebut(new \DateTimeImmutable('-1 days'))
            ->setDateFin(new \DateTimeImmutable('-1 days +12 minutes'));

        $test4->ajouterQuestionReponse(
            'Ressentez-vous de l\'inquiétude excessive ?',
            'Rarement',
            1
        );
        $test4->ajouterQuestionReponse(
            'Avez-vous des palpitations cardiaques ?',
            'Jamais',
            0
        );
        $test4->ajouterQuestionReponse(
            'Êtes-vous facilement irritable ?',
            'Parfois',
            1
        );
        $test4->ajouterQuestionReponse(
            'Avez-vous des sueurs froides ?',
            'Jamais',
            0
        );
        $test4->ajouterQuestionReponse(
            'Évitez-vous certaines situations ?',
            'Non',
            0
        );

        $test4->setAnalyse("Ce test montre un niveau d'anxiété très faible, ce qui est un bon indicateur de bien-être psychologique. Les réponses suggèrent une bonne gestion des situations stressantes et une absence de symptômes anxieux significatifs. Continuez à maintenir vos habitudes de vie saines et vos stratégies d'adaptation.");

        $manager->persist($test4);

        // Test 5 - Test en cours (non terminé)
        $test5 = new TestAdaptatif();
        $test5->setPatient($patient)
            ->setCategorie('stress')
            ->setTermine(false)
            ->setDateDebut(new \DateTimeImmutable('now'));

        $test5->ajouterQuestionReponse(
            'Comment vous sentez-vous aujourd\'hui ?',
            'Bien',
            1
        );
        $test5->ajouterQuestionReponse(
            'Avez-vous bien dormi ?',
            'Oui',
            1
        );

        $manager->persist($test5);

        $manager->flush();

        echo "✅ 5 tests adaptatifs créés pour tester les PDFs !\n";
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }
}
