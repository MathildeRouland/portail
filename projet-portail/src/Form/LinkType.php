<?php

namespace App\Form;

use App\Entity\Link;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LinkType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
         /** @var User|null $currentUser */
    $currentUser = $options['current_user'];

        $builder
            // ->add('url')
            ->add('customerName')
            // ->add('fourRandomCharacters')
            ->add('startDate', null, [
                'widget' => 'single_text',
            ])
            ->add('endDate', null, [
                'widget' => 'single_text',
            ])
            ->add('customerPhoneNumber')
            ->add('customerEmail')
            ->add('createdAt', null, [
                'widget' => 'single_text',
            ])
            // ->add('updatedAt', null, [
            //     'widget' => 'single_text',
            // ])
            ->add('status')
            ->add('creator', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'username', // Affiche le username
                'data' => $currentUser, // Pr�-s�lection du user connect�
            ])
            ->add('updater', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'username',
                'data' => $currentUser,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Link::class,
            'current_user' => null, 
        ]);
    }
}
