<?php

namespace App\Form;

use App\Entity\TestPsyScene;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TestPsySceneType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('numero', IntegerType::class, [
                'label' => 'Numéro de la scène',
                'attr' => ['class' => 'form-control']
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de Jeu',
                'choices' => [
                    'Image (Standard)' => 'IMAGE',
                    'Scénario (Histoire)' => 'SCENARIO',
                    'Simple Question' => 'SIMPLE',
                    'Mémoire (Matching)' => 'MATCHING',
                    'Puzzle (2x2)' => 'PUZZLE',
                    'Choix Couleur' => 'COLOR',
                    'Choix Émotion' => 'EMOTION',
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('titre', TextType::class, [
                'label' => 'Titre (Français)',
                'attr' => ['class' => 'form-control']
            ])
            ->add('imagePath', TextType::class, [
                'label' => 'Chemin de l\'image',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'ex: image.png']
            ])
            ->add('descriptionPsy', TextareaType::class, [
                'label' => 'Description (Psychologue)',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3]
            ])
            ->add('actif', CheckboxType::class, [
                'label' => 'Scène active',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
                'row_attr' => ['class' => 'form-check mb-3']
            ])
            ->add('reponses', CollectionType::class, [
                'label' => 'Réponses',
                'entry_type' => TestPsyReponseType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'required' => false,
                'prototype' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TestPsyScene::class,
        ]);
    }
}
