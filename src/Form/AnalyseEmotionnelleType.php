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
            ->add('emotionPrincipale')
            ->add('niveauStress')
            ->add('scoreBienEtre')
            ->add('resumeIA')
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
