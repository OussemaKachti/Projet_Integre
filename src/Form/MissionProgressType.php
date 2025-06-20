<?php

namespace App\Form;

use App\Entity\Club;
use App\Entity\Competition;
use App\Entity\MissionProgress;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MissionProgressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('progress')
            ->add('isCompleted')
            ->add('club', EntityType::class, [
                'class' => Club::class,
                'choice_label' => 'id',
            ])
            ->add('competition', EntityType::class, [
                'class' => Competition::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MissionProgress::class,
        ]);
    }
}
