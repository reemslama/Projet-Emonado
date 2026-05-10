<?php

namespace App\Form;

use App\Entity\AnalyseEmotionnelle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AnalyseEmotionnelleEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('etatEmotionnel', ChoiceType::class, [
                'label' => 'État émotionnel',
                'choices' => [
                    'Apaisé' => 'Apaise',
                    'Fragile' => 'Fragile',
                    'Stressé' => 'Stresse',
                    'Anxieux' => 'Anxieux',
                    'En surcharge' => 'En surcharge',
                    'Optimiste' => 'Optimiste',
                ],
                'required' => true,
            ])
            ->add('niveau', ChoiceType::class, [
                'label' => 'Niveau',
                'choices' => [
                    'Faible' => 'Faible',
                    'Modéré' => 'Modere',
                    'Élevé' => 'Eleve',
                ],
                'required' => true,
            ])
            ->add('declencheur', TextareaType::class, [
                'label' => 'Déclencheur',
                'required' => true,
            ])
            ->add('conseil', TextareaType::class, [
                'label' => 'Conseil',
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AnalyseEmotionnelle::class,
        ]);
    }
}