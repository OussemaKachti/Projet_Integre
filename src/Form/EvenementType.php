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

class EvenementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomEvent')
            ->add('descEvent')
            ->add('type')
            ->add('imageEvent')
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
                'choice_label' => 'nomC',  // Assurez-vous que 'nomC' est la propriété du nom du club dans votre entité Club
                'label' => 'Sélectionner un club'  // Le label affiché à l'utilisateur
            ])
            
            // Ajoutez le champ Catégorie si nécessaire
            ->add('categorie', EntityType::class, [
                'class' => Categorie::class,
                'choice_label' => 'nomCat', // Assurez-vous que 'nomCat' existe dans l'entité Categorie
                'label' => 'Sélectionner une catégorie',  // Le label affiché à l'utilisateur
                'required' => false,  // Le champ catégorie est facultatif
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Evenement::class,
        ]);
    }
}
