<?php

namespace App\Form;

use App\Entity\Saison;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Vich\UploaderBundle\Form\Type\VichImageType;

class EditSaisonType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomSaison', TextType::class, [
                'label' => 'Season Name',
            ])
            ->add('descSaison', TextareaType::class, [
                'label' => 'Season Description',
                'required' => false,
            ])
            ->add('dateFin', DateType::class, [
                'label' => 'End Date',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('imageFile', VichImageType::class, [
                'label' => 'Upload New Image (Optional)',
                'required' => false,
                'allow_delete' => false, // Prevent accidental deletion
                'download_uri' => false, // Hide download link
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Update Season',
                'attr' => ['class' => 'btn btn-primary']
            ]);
    }
    
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Saison::class,
        ]);
    }
}
