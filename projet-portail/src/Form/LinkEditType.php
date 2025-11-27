<?php

namespace App\Form;

use App\Entity\Link;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LinkEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var User|null $currentUser */
        $currentUser = $options['current_user'];

        $builder
            ->add('customerName', TextType::class, [
                'label' => 'Nom du client',
                'required' => true,
            ])
            ->add('startDate', DateTimeType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
                'required' => false,
                'input' => 'datetime_immutable',
            ])
            ->add('endDate', DateTimeType::class, [
                'label' => 'Date de fin',
                'widget' => 'single_text',
                'required' => false,
                'input' => 'datetime_immutable',
            ])
            ->add('permanent', CheckboxType::class, [
                'label' => 'Lien permanent (aucune période de validité)',
                'required' => false,
            ])
            ->add('customerPhoneNumber', TextType::class, [
                'label' => 'Numéro de téléphone',
                'required' => false,
            ])
            ->add('customerEmail', EmailType::class, [
                'label' => 'Email du client',
                'required' => false,
            ])
            ->add('status', CheckboxType::class, [
                'label' => 'Actif',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Link::class,
            'current_user' => null,
            'validation_groups' => ['Default', 'edit'],
        ]);
    }
}