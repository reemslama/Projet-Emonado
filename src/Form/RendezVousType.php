<?php

namespace App\Form;

use App\Entity\RendezVous;
use App\Entity\TypeRendezVous;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RendezVousType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomPatient', TextType::class, [
                'label' => 'Nom du patient',
                'attr' => ['class' => 'form-control']
            ])
            ->add('cin', TextType::class, [
                'label' => 'CIN',
                'attr' => ['class' => 'form-control']
            ])
            ->add('nomPsychologue', TextType::class, [
                'label' => 'Psychologue',
                'attr' => ['class' => 'form-control']
            ])
            ->add('date', DateTimeType::class, [
                'label' => 'Date et heure',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control']
            ])
            ->add('type', EntityType::class, [
                'class' => TypeRendezVous::class,
                'choice_label' => 'libelle',
                'label' => 'Type de rendez-vous',
                'attr' => ['class' => 'form-select']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RendezVous::class,
        ]);
    }
}