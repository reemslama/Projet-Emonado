<?php

namespace App\Form; // Vérifiez bien cette ligne

use App\Entity\RendezVous;
use App\Entity\TypeRendezVous;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

class RendezVousType extends AbstractType // Le nom ici doit être identique au nom du fichier
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom_patient', TextType::class, ['label' => 'Nom du Patient'])
            ->add('cin', TextType::class, ['label' => 'Numéro CIN'])
            ->add('nom_psychologue', TextType::class, ['label' => 'Psychologue'])
            ->add('date', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Date et Heure'
            ])
            ->add('status', TextType::class, ['label' => 'Statut'])
            ->add('type', EntityType::class, [
                'class' => TypeRendezVous::class,
                'choice_label' => 'description',
                'label' => 'Type de RDV'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RendezVous::class,
        ]);
    }
}