<?php

namespace App\Form;

use App\Entity\Club;
use App\Entity\Produit;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\DateType;

class ProduitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomProd')
            ->add('descProd')
            ->add('prix')
            ->add('imgProd', FileType::class,[
                'required'=>false,
                'mapped'=>false,
                'constraints' =>[new Image(['maxSize'=>'8000k'])]
            ])
            ->add('createdAt' ,DateType::class, [
                'widget' => 'single_text',
                'mapped' => false,
            ])
            ->add('quantity')
            ->add('club', EntityType::class, [
                'class' => Club::class,
                'choice_label' => 'nomC',
                'placeholder' => 'Choose a club',
                
                
            
            ])
            ->add('save', SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Produit::class,
        ]);
    }
}
