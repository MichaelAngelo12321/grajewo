<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Advertisement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\AdvertisementCategory;

class AdvertisementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('category', EntityType::class, [
                'class' => AdvertisementCategory::class,
                'label' => 'Kategoria',
                'placeholder' => 'Wybierz kategorię',
            ])
            ->add('title', TextType::class, [
                'attr' => [
                    'class' => 'form-control-lg',
                ],
                'label' => 'Tytuł',
            ])
            ->add('content', TextareaType::class, [
                'attr' => [
                    'rows' => 5,
                ],
                'label' => 'Treść',
            ])
            ->add('author', TextType::class, [
                'label' => 'Twój podpis',
                'required' => true,
            ])
            ->add('phone', TelType::class, [
                'label' => 'Telefon (opcjonalnie jeśli podasz email)',
                'required' => false,
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email (opcjonalnie jeśli podasz telefon)',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Advertisement::class,
        ]);
    }
}
