<?php

namespace App\Form;

use App\Entity\Reponse;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReponseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // 1. Texte de la réponse (le plus important)
            ->add('texte', TextType::class, [
                'label'       => false,               // pas de label → plus aéré
                'attr'        => [
                    'placeholder' => 'Réponse (ex: Pas du tout, Oui, Rarement...)',
                    'class'       => 'form-control',
                ],
                'required'    => true,
            ])

            // 2. Valeur / score (très court)
            ->add('valeur', IntegerType::class, [
                'label'       => false,
                'attr'        => [
                    'placeholder' => 'Score',
                    'class'       => 'form-control text-center',
                    'style'       => 'max-width: 90px;',
                    'min'         => -5,
                    'max'         => 5,
                ],
                'required'    => true,
            ])
        ;
        // → on enlève complètement le champ 'ordre' pour simplifier
        //   (on peut trier par défaut ou le gérer autrement plus tard si besoin)
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reponse::class,
            'label'      => false,
            'attr'       => [
                'class' => 'row g-2 align-items-center reponse-row',
            ],
        ]);
    }
}