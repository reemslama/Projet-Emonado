<?php

namespace App\Form;

use App\Entity\RendezVous;
use App\Entity\TypeRendezVous;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class RendezVousType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Psychologue choisi : le lien réel est le créneau `disponibilite` (dispo_id) côté table rendez_vous.
            ->add('psychologue', EntityType::class, [
                'class' => User::class,
                'mapped' => false,
                'required' => true,
                'label' => 'Psychiatre / Psychologue',
                'choice_label' => static function (User $u): string {
                    $nom = trim((string) $u->getNom());
                    $prenom = trim((string) $u->getPrenom());
                    $full = trim($nom . ' ' . $prenom);
                    return $full !== '' ? $full : $u->getEmail();
                },
                'placeholder' => 'Choisir un psychologue',
                // id HTML fixé dans `new.html.twig` via option `id:` (Symfony ignore attr.id au profit de vars.id).
                'attr' => ['class' => 'form-select'],
                'query_builder' => static function (\Doctrine\ORM\EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->andWhere('u.roles LIKE :role')
                        ->setParameter('role', '%"ROLE_PSYCHOLOGUE"%')
                        ->orderBy('u.nom', 'ASC')
                        ->addOrderBy('u.prenom', 'ASC');
                },
            ])
            ->add('type', EntityType::class, [
                'class' => TypeRendezVous::class,
                'choice_label' => 'libelle',
                'label' => 'Type de rendez-vous',
                'attr' => ['class' => 'form-select']
            ])
            // Date + créneau (unifiés en DateTime côté contrôleur)
            ->add('jour', DateType::class, [
                'mapped' => false,
                'required' => true,
                'label' => 'Calendrier',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ])
            // Rempli côté client via liste déroulante (pas ChoiceType vide = sinon « selected choice is invalid »)
            ->add('creneau', HiddenType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Créneaux disponibles',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez choisir un créneau.']),
                    new Regex([
                        'pattern' => '/^\d{2}:\d{2}$/',
                        'message' => 'Créneau invalide.',
                    ]),
                ],
            ])
            // Note patient → colonne notes_patient (rendez_vous), remplie dans le contrôleur
            ->add('notePatient', TextareaType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Notes du patient',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Expliquez clairement votre demande, vos symptômes ou les points à aborder pendant le rendez-vous…',
                ],
            ])
            // Position → latitude / longitude / adresse sur rendez_vous
            ->add('latitude', HiddenType::class, [
                'mapped' => false,
                'required' => false,
                'attr' => [],
            ])
            ->add('longitude', HiddenType::class, [
                'mapped' => false,
                'required' => false,
                'attr' => [],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RendezVous::class,
        ]);
    }
}