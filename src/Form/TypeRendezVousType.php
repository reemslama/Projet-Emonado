<?php

namespace App\Form;

use App\Entity\TypeRendezVous;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

class TypeRendezVousType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('libelle', TextType::class, [
                'label' => 'Libell├®',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Urgence, Suivi, Consultation...'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le libell├® est obligatoire']),
                    new Length([
                        'min' => 2,
                        'max' => 255,
                        'minMessage' => 'Le libell├® doit contenir au moins {{ limit }} caract├¿res',
                        'maxMessage' => 'Le libell├® ne peut pas d├®passer {{ limit }} caract├¿res'
                    ])
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Description optionnelle du type de rendez-vous...'
                ],
                'constraints' => [
                    new Length([
                        'max' => 500,
                        'maxMessage' => 'La description ne peut pas d├®passer {{ limit }} caract├¿res'
                    ])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TypeRendezVous::class,
        ]);
    }
}
