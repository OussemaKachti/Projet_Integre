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
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;



class EvenementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {     $today = new \DateTime();
        $tomorrow = $today->modify('+1 day')->format('Y-m-d'); 
        $builder
   
            ->add('nomEvent')
            ->add('descEvent')
            ->add('type', ChoiceType::class, [
                'label' => 'Event Type',
                'choices' => [
                    'Open' => 'open',   // La valeur qui sera enregistrée dans la base de données
                    'Closed' => 'closed', // La valeur qui sera enregistrée dans la base de données
                ],
                'expanded' => true,  // Utilise des boutons radio au lieu d'un menu déroulant
                'multiple' => false, // Empêche la sélection multiple
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please select the event type.',
                    ]),
                ],
            ])
            
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
        'label' => 'Start Date',
        'attr' => [
            'min' => (new \DateTime())->format('Y-m-d'),  // Limiter le champ au jour actuel
        ],
        'constraints' => [
            new Callback(function ($value, ExecutionContextInterface $context) {
                if ($value < new \DateTime()) {
                    $context->buildViolation('The start date cannot be in the past.')
                        ->atPath('startDate') // Si la date est invalide, positionner l'erreur sur ce champ
                        ->addViolation();
                }
            }),
        ],
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
