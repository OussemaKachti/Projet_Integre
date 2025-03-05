<?php

namespace App\Form;

use App\Entity\Competition;
use App\Entity\Saison;
use App\Enum\GoalTypeEnum;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class CompetitionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
    ->add('nomComp', TextType::class)
    ->add('descComp', TextareaType::class)
    ->add('points', IntegerType::class)
    ->add('goal', IntegerType::class, [
        'label' => 'Goal (e.g., number of events)',
    ])
    ->add('goalType', ChoiceType::class, [
        'choices' => [
            'Event Count' => GoalTypeEnum::EVENT_COUNT,
            'Event Likes' => GoalTypeEnum::EVENT_LIKES,
            'Member Count' => GoalTypeEnum::MEMBER_COUNT,
        ],
        'label' => 'Goal Type',
    ])
    ->add('status', ChoiceType::class, [
        'choices' => [
            'Pending' => 'pending',
            'Activated' => 'activated',
            'Deactivated' => 'desactivated',
            'Expired' => 'expired',
        ],
        'label' => 'Status',
    ])
    ->add('startDate', DateType::class, [
        'widget' => 'single_text', // 🛠 Ensures HTML date picker works
    ])
    ->add('endDate', DateType::class, [
        'widget' => 'single_text',
    ])
    ->add('saison', EntityType::class, [
        'class' => Saison::class,
        'choice_label' => 'nomSaison', // This tells Symfony to display the season name
        'placeholder' => 'Select a season', // Optional, adds a default empty choice
    ])
    ->add('save', SubmitType::class, ['label' => 'Create Competition']);

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Competition::class,
        ]);
    }
}
