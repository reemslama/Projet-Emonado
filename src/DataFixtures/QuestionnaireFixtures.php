<?php

namespace App\DataFixtures;

use App\Entity\Question;
use App\Entity\Reponse;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class QuestionnaireFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $data = [

            // ================= TEST STRESS =================
            [
                'texte' => "Comment évaluez-vous votre stress ?",
                'ordre' => 1,
                'type' => 'choix_unique',
                'categorie' => 'stress',
                'reponses' => [
                    ['Faible', 0],
                    ['Modéré', 1],
                    ['Élevé', 2],
                    ['Très élevé', 3],
                ]
            ],
            [
                'texte' => "Avez-vous des difficultés à dormir à cause du stress ?",
                'ordre' => 2,
                'type' => 'choix_unique',
                'categorie' => 'stress',
                'reponses' => [
                    ['Jamais', 0],
                    ['Parfois', 1],
                    ['Souvent', 2],
                    ['Toujours', 3],
                ]
            ],
            [
                'texte' => "Vous sentez-vous tendu(e) ou nerveux(se) ?",
                'ordre' => 3,
                'type' => 'choix_unique',
                'categorie' => 'stress',
                'reponses' => [
                    ['Pas du tout', 0],
                    ['Un peu', 1],
                    ['Beaucoup', 2],
                    ['Extrêmement', 3],
                ]
            ],
            [
                'texte' => "Avez-vous du mal à vous détendre ?",
                'ordre' => 4,
                'type' => 'choix_unique',
                'categorie' => 'stress',
                'reponses' => [
                    ['Jamais', 0],
                    ['Parfois', 1],
                    ['Souvent', 2],
                    ['Toujours', 3],
                ]
            ],
            [
                'texte' => "Vous sentez-vous dépassé(e) par vos responsabilités ?",
                'ordre' => 5,
                'type' => 'choix_unique',
                'categorie' => 'stress',
                'reponses' => [
                    ['Pas du tout', 0],
                    ['Un peu', 1],
                    ['Beaucoup', 2],
                    ['Totalement', 3],
                ]
            ],
            [
                'texte' => "Avez-vous des maux de tête ou tensions physiques liés au stress ?",
                'ordre' => 6,
                'type' => 'choix_unique',
                'categorie' => 'stress',
                'reponses' => [
                    ['Jamais', 0],
                    ['Parfois', 1],
                    ['Souvent', 2],
                    ['Très souvent', 3],
                ]
            ],

            // ================= TEST DEPRESSION =================
            [
                'texte' => "Vous sentez-vous triste ?",
                'ordre' => 1,
                'type' => 'choix_unique',
                'categorie' => 'depression',
                'reponses' => [
                    ['Jamais', 0],
                    ['Parfois', 1],
                    ['Souvent', 2],
                    ['Toujours', 3],
                ]
            ],
            [
                'texte' => "Avez-vous perdu de l'intérêt ou du plaisir pour les activités que vous aimiez auparavant ?",
                'ordre' => 2,
                'type' => 'choix_unique',
                'categorie' => 'depression',
                'reponses' => [
                    ['Jamais', 0],
                    ['Parfois', 1],
                    ['Souvent', 2],
                    ['Toujours', 3],
                ]
            ],
            [
                'texte' => "Vous sentez-vous fatigué(e) ou sans énergie, même sans avoir fait d'effort particulier ?",
                'ordre' => 3,
                'type' => 'choix_unique',
                'categorie' => 'depression',
                'reponses' => [
                    ['Jamais', 0],
                    ['Parfois', 1],
                    ['Souvent', 2],
                    ['Toujours', 3],
                ]
            ],
            [
                'texte' => "Avez-vous des troubles du sommeil (difficulté à vous endormir, réveils nocturnes, sommeil trop long) ?",
                'ordre' => 4,
                'type' => 'choix_unique',
                'categorie' => 'depression',
                'reponses' => [
                    ['Jamais', 0],
                    ['Parfois', 1],
                    ['Souvent', 2],
                    ['Toujours', 3],
                ]
            ],
            [
                'texte' => "Votre appétit a-t-il changé (manger beaucoup plus ou beaucoup moins que d’habitude) ?",
                'ordre' => 5,
                'type' => 'choix_unique',
                'categorie' => 'depression',
                'reponses' => [
                    ['Jamais', 0],
                    ['Parfois', 1],
                    ['Souvent', 2],
                    ['Toujours', 3],
                ]
            ],

            // ================= TEST IQ =================
            [
                'texte' => "2 + 2 = ?",
                'ordre' => 1,
                'type' => 'choix_unique',
                'categorie' => 'iq',
                'reponses' => [
                    ['3', 0],
                    ['4', 1],
                    ['5', 0],
                ]
            ],
            [
                'texte' => "Quelle est la suite logique : 3, 6, 12, 24, ... ?",
                'ordre' => 2,
                'type' => 'choix_unique',
                'categorie' => 'iq',
                'reponses' => [
                    ['36', 0],
                    ['48', 1],
                    ['60', 0],
                    ['72', 0],
                ]
            ],
            [
                'texte' => "Chat est à Miauler comme Chien est à :",
                'ordre' => 3,
                'type' => 'choix_unique',
                'categorie' => 'iq',
                'reponses' => [
                    ['Courir', 0],
                    ['Aboier', 1],
                    ['Manger', 0],
                    ['Dormir', 0],
                ]
            ],
            [
                'texte' => "Si tous les Zigs sont des Zags et que certains Zags sont des Zogs, alors :",
                'ordre' => 4,
                'type' => 'choix_unique',
                'categorie' => 'iq',
                'reponses' => [
                    ['Tous les Zigs sont des Zogs', 0],
                    ['Certains Zigs peuvent être des Zogs', 1],
                    ['Aucun Zig n’est un Zog', 0],
                    ['Tous les Zogs sont des Zigs', 0],
                ]
            ],
            [
                'texte' => "2, 3, 5, 7, 11, ... Quel est le nombre suivant ?",
                'ordre' => 5,
                'type' => 'choix_unique',
                'categorie' => 'iq',
                'reponses' => [
                    ['13', 1],
                    ['12', 0],
                    ['15', 0],
                    ['17', 0],
                ]
            ],

        ];

        foreach ($data as $qData) {
            $question = new Question();
            $question->setTexte($qData['texte'])
                     ->setOrdre($qData['ordre'])
                     ->setTypeQuestion($qData['type'])
                     ->setCategorie($qData['categorie']);

            foreach ($qData['reponses'] as $index => $r) {
                $rep = new Reponse();
                $rep->setTexte($r[0])
                    ->setValeur($r[1])
                    ->setOrdre($index + 1);

                $question->addReponse($rep);
            }

            $manager->persist($question);
        }

        $manager->flush();
    }
}
