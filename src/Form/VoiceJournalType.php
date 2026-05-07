<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotNull;

class VoiceJournalType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('voiceNote', FileType::class, [
            'label' => 'Fichier vocal',
            'mapped' => false,
            'required' => true,
            'constraints' => [
                new NotNull(['message' => 'Merci d ajouter un fichier audio.']),
                new File([
                    'maxSize' => '50M',
                    'extensions' => ['mp3', 'wav', 'webm', 'ogg', 'm4a', 'mp4'],
                    'extensionsMessage' => 'Format non supporte. Utilisez mp3, wav, webm, ogg, m4a ou mp4.',
                ]),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
