<?php

namespace App\Form;

use App\Entity\Sondage;
use App\Entity\ChoixSondage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SondageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('question', TextType::class, [
                'label' => 'Question du sondage',
                'attr' => ['class' => 'form-control']
            ])
            ->add('choix', CollectionType::class, [
                'entry_type' => ChoixSondageType::class,
                'allow_add' => true, // Permet l'ajout dynamique de choix
                'by_reference' => false,
                'label' => false
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Créer le sondage',
                'attr' => ['class' => 'btn btn-primary']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Sondage::class,
        ]);
    }
}