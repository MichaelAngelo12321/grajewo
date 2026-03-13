<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Setting;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SettingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'] ?? false;

        $builder
            ->add('name', TextType::class, [
                'label' => 'Nazwa ustawienia (Klucz)',
                'help' => 'Unikalny identyfikator ustawienia (np. site_name)',
                'disabled' => $isEdit,
            ])
            ->add('value', TextareaType::class, [
                'label' => 'Wartość',
                'attr' => ['rows' => 5],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Setting::class,
            'is_edit' => false,
        ]);
    }
}
