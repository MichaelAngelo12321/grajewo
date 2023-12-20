<?php

namespace App\Form;

use App\Entity\GasStation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GasStationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, ['label' => 'Nazwa stacji'])
            ->add('address', null, ['label' => 'Adres stacji'])
            ->add('hasDiesel', null, ['label' => 'Posiada w ofercie olej napędowy'])
            ->add('hasUnleaded', null, ['label' => 'Posiada w ofercie benzynę bezołowiową'])
            ->add('hasSuperUnleaded', null, ['label' => 'Posiada w ofercie benzynę bezołowiową 98'])
            ->add('hasLiquidGas', null, ['label' => 'Posiada w ofercie gaz płynny'])
            ->add('submit', SubmitType::class, ['label' => 'Dodaj stację'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => GasStation::class,
        ]);
    }
}
