<?php
namespace App\Form;

use App\Entity\Categorie;
use App\Entity\Club;
use App\Entity\Evenement;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;


class EvenementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomEvent')
            ->add('descEvent')
            ->add('type')
            // src/Form/EvenementType.php



    // Ajoutez ce champ à votre formulaire
    ->add('imageDescription', FileType::class, [
        'label' => 'Image de la description (facultatif)',
        'required' => false,
        'mapped' => false, // Cela indique qu'on ne l'associe pas directement à un champ de l'entité
        'attr' => ['accept' => 'image/*'],
    ])
    // Autres champs du formulaire...


           
   

            ->add('startDate', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Date de début',
            ])
            ->add('endDate', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Date de fin',
                'required' => false, // Champ optionnel
            ])
            ->add('lieux')
            
            // Ajouter le champ Club avec un label explicite
            ->add('club', EntityType::class, [
                'class' => Club::class,
                'choice_label' => 'nomC', 
                'label' => 'Sélectionner un club',
                'placeholder' => 'Choisir un club',
            ])
            
            
            
            // Ajoutez le champ Catégorie si nécessaire
           
            ->add('categorie', EntityType::class, [
                'class' => Categorie::class,
                'choice_label' => 'nomCat', 
                'label' => 'Sélectionner une catégorie',  
                'placeholder' => 'Choisir une cat',
            ])
          
;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Evenement::class,
        ]);
    }
}
