<?php

namespace App\Form;

use App\Entity\TestPsyReponse;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TestPsyReponseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('label', TextType::class, [
                'label' => 'Label (Français)',
                'required' => false,
                'attr' => ['class' => 'form-control form-control-sm', 'placeholder' => 'ex: Joyeux'],
            ])
            ->add('emoji', TextType::class, [
                'label' => 'Emoji',
                'required' => false,
                'attr' => ['class' => 'form-control form-control-sm', 'placeholder' => '😊'],
            ])
            ->add('imagePath', TextType::class, [
                'label' => 'Image (chemin)',
                'required' => false,
                'attr' => ['class' => 'form-control form-control-sm', 'placeholder' => 'ex: joie.png'],
            ])
            ->add('etatDesc', TextType::class, [
                'label' => 'Description état',
                'required' => false,
                'attr' => ['class' => 'form-control form-control-sm', 'placeholder' => 'ex: Heureux et souriant'],
            ])
            ->add('poidsAnxiete', IntegerType::class, [
                'label' => 'Poids Anxiété',
                'required' => false,
                'data' => 0,
                'attr' => ['class' => 'form-control form-control-sm', 'min' => 0, 'max' => 10],
            ])
            ->add('poidsTristesse', IntegerType::class, [
                'label' => 'Poids Tristesse',
                'required' => false,
                'data' => 0,
                'attr' => ['class' => 'form-control form-control-sm', 'min' => 0, 'max' => 10],
            ])
            ->add('poidsColere', IntegerType::class, [
                'label' => 'Poids Colère',
                'required' => false,
                'data' => 0,
                'attr' => ['class' => 'form-control form-control-sm', 'min' => 0, 'max' => 10],
            ])
            ->add('poidsJoie', IntegerType::class, [
                'label' => 'Poids Joie',
                'required' => false,
                'data' => 0,
                'attr' => ['class' => 'form-control form-control-sm', 'min' => 0, 'max' => 10],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TestPsyReponse::class,
        ]);
    }
}
