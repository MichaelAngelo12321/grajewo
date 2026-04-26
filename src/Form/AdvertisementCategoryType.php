<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\AdvertisementCategory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdvertisementCategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nazwa kategorii',
            ])
            ->add('slug', TextType::class, [
                'label' => 'Slug',
                'required' => false,
                'help' => 'Zostaw puste, aby wygenerować automatycznie.',
            ])
            ->add('iconName', TextType::class, [
                'label' => 'Nazwa ikony',
                'help' => 'Nazwa ikony z Tabler Icons (np. ti-car)',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AdvertisementCategory::class,
        ]);
    }
}