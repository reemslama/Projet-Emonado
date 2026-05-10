<?php

namespace App\Form;

use App\Entity\AnalyseEmotionnelle;
use App\Entity\Journal;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AnalyseEmotionnelleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('etatEmotionnel', null, [
                'label' => 'État émotionnel',
                'required' => false,
            ])
            ->add('niveau', null, [
                'label' => 'Niveau',
                'required' => false,
            ])
            ->add('declencheur', null, [
                'label' => 'Déclencheur',
                'required' => false,
            ])
            ->add('conseil', null, [
                'label' => 'Conseil',
                'required' => false,
            ])
            ->add('dateAnalyse')
            ->add('journal', EntityType::class, [
                'class' => Journal::class,
                'choice_label' => 'id',
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
