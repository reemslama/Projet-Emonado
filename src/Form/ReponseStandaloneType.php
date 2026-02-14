<?php

namespace App\Form;

use App\Entity\Reponse;
use App\Entity\Question;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReponseStandaloneType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Sélection de la question associée
            ->add('question', EntityType::class, [
                'class' => Question::class,
                'choice_label' => function(Question $question) {
                    return '#' . $question->getId() . ' - ' . substr($question->getTexte(), 0, 50) . '...';
                },
                'label' => 'Question associée',
                'attr' => [
                    'class' => 'form-control'
                ],
                'placeholder' => 'Sélectionnez une question',
                'required' => true
            ])
            
            // Texte de la réponse
            ->add('texte', TextType::class, [
                'label' => 'Texte de la réponse',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Jamais, Parfois, Souvent...'
                ],
                'required' => true
            ])
            
            // Score/Valeur
            ->add('valeur', IntegerType::class, [
                'label' => 'Score/Valeur',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: 0, 1, 2, 3...',
                    'min' => -5,
                    'max' => 5
                ],
                'required' => true
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reponse::class,
        ]);
    }
}

