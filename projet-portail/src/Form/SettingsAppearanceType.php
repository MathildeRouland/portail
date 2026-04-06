<?php

namespace App\Form;

use App\Entity\Settings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use FOS\CKEditorBundle\Form\Type\CKEditorType;


class SettingsAppearanceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
       $builder
            ->add('homepageText', CKEditorType::class, [
                'required' => false,
                'config' => [
                    'toolbar' => 'standard',
                ],
            ])
            ->add('backgroundImage', FileType::class, [
            'label' => 'Image de fond',
            'mapped' => false,       
            'required' => false,
                ])
            ->add('removeBackground', CheckboxType::class, [
                'label' => 'Supprimer l’image existante',
                'mapped' => false,
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Settings::class,
        ]);
    }
}
