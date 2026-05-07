<?php

namespace App\Form;

use App\Entity\Question;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuestionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('texte', TextareaType::class, [
                'label' => 'Texte de la question',
                'required' => true,
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'Entrez la question ici...',
                ],
            ])

            ->add('ordre', IntegerType::class, [
                'label' => 'Ordre d\'affichage',
                'required' => true,
                'attr' => [
                    'min' => 1,
                    'placeholder' => '1, 2, 3...',
                ],
            ])

            ->add('typeQuestion', ChoiceType::class, [
                'label' => 'Type de question',
                'choices' => [
                    'Choix unique (radio)' => 'choix_unique',
                    'Choix multiple (checkbox)' => 'choix_multiple',
                    // Tu pourras ajouter plus tard : 'Échelle (Likert)', 'Texte libre', etc.
                ],
                'placeholder' => 'Choisissez un type...',
                'required' => true,
            ])

            ->add('categorie', ChoiceType::class, [
                'label' => 'Catégorie',
                'choices' => [
                    'Stress' => 'stress',
                    'Dépression' => 'depression',
                    'Anxiété' => 'anxiete',
                    'QI / Capacités cognitives' => 'iq',
                    'Estime de soi' => 'estime_soi',
                    'Sommeil' => 'sommeil',
                    // Ajoute ici les catégories que tu utilises vraiment
                ],
                'placeholder' => 'Sélectionnez une catégorie...',
                'required' => true,
            ])

            ->add('reponses', CollectionType::class, [
                'entry_type' => ReponseType::class,
                'entry_options' => [
                    'label' => false,
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => 'Réponses possibles',
                'attr' => [
                    'class' => 'reponses-collection',
                ],
                'prototype' => true,          // Important pour le JavaScript
                'delete_empty' => true,       // Supprime les réponses vides
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Question::class,
            'attr' => ['novalidate' => 'novalidate'], // optionnel : désactive la validation HTML5 par défaut
        ]);
    }
}