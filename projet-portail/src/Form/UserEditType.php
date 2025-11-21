<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;


class UserEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
{
    /** @var User|null $currentUser */
    $currentUser = $options['current_user'];

    // Rôles possibles
    $roleChoices = [
        'Utilisateur' => 'ROLE_USER',
        'Administrateur' => 'ROLE_ADMIN',
        'Super Admin' => 'ROLE_SUPER_ADMIN',
    ];

    // Filtrer si l'utilisateur connecté n'est pas superadmin
    if (!$currentUser?->getRoles() || !in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles())) {
        unset($roleChoices['Super Admin']);
    }

    $builder
        ->add('username', null, [
            'label' => 'Nom d\'utilisateur',
            'required' => true,
        ])
        ->add('email', null, [
            'label' => 'Email',
            'required' => true,
        ])
        ->add('password', \Symfony\Component\Form\Extension\Core\Type\PasswordType::class, [
            'label' => 'Mot de passe (laisser vide pour ne pas changer)',
            'mapped' => false,
            'required' => false,
        ])
        ->add('roles', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
            'choices' => $roleChoices,
            'multiple' => false,
            'expanded' => false,
            'label' => 'Rôle',
            'mapped' => false, // tu gères le setRoles à la main dans le contrôleur
            'data' => $options['data']->getRoles()[0] ?? 'ROLE_USER', // préselection du rôle actuel
        ]);
}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'current_user' => null, 
            //'validation_groups' => ['edit']
        ]);
    }
}