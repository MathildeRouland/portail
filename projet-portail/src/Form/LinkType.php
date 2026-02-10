<?php

namespace App\Form;

use App\Entity\Link;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Regex;

class LinkType extends AbstractType
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
                'data' => (new \DateTimeImmutable())->setTime(8, 0), 
            ])
            ->add('endDate', DateTimeType::class, [
                'label' => 'Date de fin',
                'widget' => 'single_text',
                'required' => false,
                'input' => 'datetime_immutable',
                'data' => (new \DateTimeImmutable())->setTime(8, 0), 
            ])
            ->add('permanent', CheckboxType::class, [
                'label' => 'Lien permanent (aucune période de validité)',
                'required' => false,
            ])
            ->add('customerPhoneNumber', TextType::class, [
                'label' => 'Numéro de téléphone',
                'required' => false,
                'constraints' => [
                    new Regex([
                        'pattern' => "/^(?:\+[\d]{1,3}\s?\d{4,14}|\(?0[67]\)?\s?\d{2}(\s?\d{2}){3})$/",
                        'message' => "Le numéro de téléphone doit être au format français 06 xx xx xx xx ou international E.164 (ex : +33123456789).",
                        'groups' => ['create', 'edit'],
                    ])
                ],
            ])
            ->add('customerEmail', EmailType::class, [
                'label' => 'Email du client',
                'required' => false,
            ])
            ->add('status', CheckboxType::class, [
                'label' => 'Actif',
                'required' => false,
            ])
            
            // creator/updater/createdAt are set in the controller/entity lifecycle and shouldn't be part of the create form
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Link::class,
            'current_user' => null, 
            'validation_groups' => ['Default', 'create'],
        ]);
    }
}
