<?php

namespace App\Form;

use App\Entity\Consultation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConsultationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dateConsultation', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Date de la consultation',
                'required' => true,
            ])
            ->add('motif', TextareaType::class, [
                'label' => 'Motif de consultation',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('examenClinique', TextareaType::class, [
                'label' => 'Examen clinique',
                'required' => false,
                'attr' => ['rows' => 5],
            ])
            ->add('diagnostic', TextareaType::class, [
                'label' => 'Diagnostic',
                'required' => false,
                'attr' => ['rows' => 4],
            ])
            ->add('planTherapeutique', TextareaType::class, [
                'label' => 'Plan thérapeutique / Conduite à tenir',
                'required' => false,
                'attr' => ['rows' => 5],
            ])
            ->add('prochainRdv', TextType::class, [
                'label' => 'Prochain rendez-vous (date ou texte libre)',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Consultation::class,
        ]);
    }
}