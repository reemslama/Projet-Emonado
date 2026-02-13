<?php

namespace App\Form;

use App\Entity\RendezVous;
use App\Entity\TypeRendezVous;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Type;

class RendezVousType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomPatient', TextType::class, [
                'label' => 'Nom du patient',
                'constraints' => [
                    new NotBlank(['message' => 'Le nom du patient est obligatoire']),
                    new Length([
                        'min' => 2,
                        'max' => 255,
                        'minMessage' => 'Le nom doit contenir au moins {{ limit }} caract├¿res',
                        'maxMessage' => 'Le nom ne peut pas d├®passer {{ limit }} caract├¿res'
                    ]),
                    new Regex([
                        'pattern' => '/^[a-zA-Z├Ç-├┐\s\-]+$/',
                        'message' => 'Le nom ne peut contenir que des lettres, espaces et tirets'
                    ])
                ]
            ])
            ->add('cin', TextType::class, [
                'label' => 'CIN',
                'constraints' => [
                    new NotBlank(['message' => 'Le CIN est obligatoire']),
                    new Regex([
                        'pattern' => '/^[0-9]{8,12}$/',
                        'message' => 'Le CIN doit contenir entre 8 et 12 chiffres'
                    ])
                ]
            ])
            ->add('nomPsychologue', TextType::class, [
                'label' => 'Psychologue',
                'constraints' => [
                    new NotBlank(['message' => 'Le nom du psychologue est obligatoire']),
                    new Length([
                        'min' => 2,
                        'max' => 255,
                        'minMessage' => 'Le nom doit contenir au moins {{ limit }} caract├¿res',
                        'maxMessage' => 'Le nom ne peut pas d├®passer {{ limit }} caract├¿res'
                    ])
                ]
            ])
            ->add('date', DateTimeType::class, [
                'label' => 'Date et heure',
                'widget' => 'single_text',
                'constraints' => [
                    new NotBlank(['message' => 'La date est obligatoire']),
                    new Type([
                        'type' => \DateTimeInterface::class,
                        'message' => 'Date invalide'
                    ])
                ]
            ])
            ->add('type', EntityType::class, [
                'class' => TypeRendezVous::class,
                'choice_label' => 'libelle',
                'label' => 'Type de rendez-vous',
                'placeholder' => 'S├®lectionnez un type',
                'constraints' => [
                    new NotBlank(['message' => 'Le type de rendez-vous est obligatoire'])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RendezVous::class,
            'csrf_protection' => true,
            'validation_groups' => ['Default']
        ]);
    }
}
