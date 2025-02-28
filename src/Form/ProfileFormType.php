<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProfileFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('prenom', TextType::class, [
                'label' => 'First Name',
                'attr' => [
                    'placeholder' => 'John',
                    'class' => 'form-control rounded-end',
                ],
                'row_attr' => [
                    'class' => 'mb-4',
                ],
                'label_attr' => [
                    'class' => 'form-label text-dark fw-bold mb-2',
                ],
            ])
            ->add('nom', TextType::class, [
                'label' => 'Last Name',
                'attr' => [
                    'placeholder' => 'Doe',
                    'class' => 'form-control rounded-end',
                ],
                'row_attr' => [
                    'class' => 'mb-4',
                ],
                'label_attr' => [
                    'class' => 'form-label text-dark fw-bold mb-2',
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email Address',
                'attr' => [
                    'placeholder' => 'john@example.com',
                    'class' => 'form-control rounded-end',
                ],
                'row_attr' => [
                    'class' => 'mb-4',
                ],
                'label_attr' => [
                    'class' => 'form-label text-dark fw-bold mb-2',
                ],
            ])
            ->add('tel', TextType::class, [
                'label' => 'Phone Number',
                'attr' => [
                    'placeholder' => '+216 12 345 678',
                    'class' => 'form-control rounded-end',
                ],
                'row_attr' => [
                    'class' => 'mb-4',
                ],
                'label_attr' => [
                    'class' => 'form-label text-dark fw-bold mb-2',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'update_profile',
        ]);
    }
}