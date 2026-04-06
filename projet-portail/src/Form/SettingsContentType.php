<?php

namespace App\Form;

use App\Entity\Settings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;


class SettingsContentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('portalText', TextareaType::class, [
            'label' => 'modifier le texte actuel',
            'required' => false,
            'attr' => ['class' => 'js-ckeditor']
        ])
        ->add('removePortalText', CheckboxType::class, [
            'mapped' => false,
            'required' => false,
            'label' => 'Supprimer le texte du portail'
        ])
        ->add('rgpdFile', FileType::class, [
            'mapped' => false,
            'required' => false
        ])
        ->add('removeRgpd', CheckboxType::class, [
            'label' => 'Supprimer le fichier existant',
            'mapped' => false,
            'required' => false
        ]);        
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Settings::class,
        ]);
    }
}
