<?php
namespace App\Form;

use App\Entity\Categorie;
use App\Entity\Club;
use App\Entity\Evenement;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EvenementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomEvent')
            ->add('descEvent')
            ->add('type', ChoiceType::class, [
                'label' => 'Event Type',
                'choices' => [
                    'Open' => 'open',
                    'Closed' => 'closed',
                ],
                'expanded' => true,
                'multiple' => false,
            ])
            ->add('imageDescription', FileType::class, [
                'label' => 'Image de la description (facultatif)',
                'required' => false,
                'mapped' => false,
                'attr' => ['accept' => 'image/*'],
            ])
            ->add('startDate', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Start Date',
                'empty_data' => null,
                'attr' => [
                    'min' => (new \DateTime())->format('Y-m-d'),
                ],
            ])
            ->add('endDate', DateTimeType::class, [
                'widget' => 'single_text',
                'empty_data' => null,
                'label' => 'Date de fin',
                'required' => false,
            ])
            ->add('lieux')
            ->add('club', EntityType::class, [
                'class' => Club::class,
                'choice_label' => 'nomC',
                'label' => 'Sélectionner un club',
                'placeholder' => 'Choisir un club',
            ])
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
