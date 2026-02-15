<?php

namespace App\Form;

use App\Entity\Journal;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class JournalType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('humeur', ChoiceType::class, [
                'choices' => [
                    'Heureux' => 'heureux',
                    'Calme' => 'calme',
                    'SOS' => 'SOS',
                    'En colère' => 'en colere',
                ],
                'placeholder' => 'Sélectionnez une humeur',
                'required' => false,
            ])
            ->add('contenu')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Journal::class,
        ]);
    }
}
