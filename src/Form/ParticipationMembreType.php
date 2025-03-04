<?php
// src/Form/ParticipationMembreType.php

namespace App\Form;

use App\Entity\Club;
use App\Entity\ParticipationMembre;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;


class ParticipationMembreType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('description', TextareaType::class, [
                'required' => false,
                'label' => 'Description',
                'attr' => [
                    'placeholder' => 'Ajoutez une description...',
                ],
            ]);

    
            //->add('user', EntityType::class, [
               // 'class' => User::class,
                //'choice_label' => function (User $user) {
                    //return $user->getId(); // Affiche uniquement l'ID
              // },
                //'label' => 'Membre',
                //'placeholder' => 'Sélectionnez un membre',
            //]);
          
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ParticipationMembre::class,
        ]);
    }
}
