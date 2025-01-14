<?php

namespace App\Form;

use App\Entity\StaticPage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StaticPageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Tytuł',
            ])
            ->add('slug', TextType::class, [
                'required' => false,
                'disabled' => true,
                'label' => 'Slug',
                'help' => 'Generowany automatycznie po zapisie na podstawie tytułu',
            ])
            ->add('content', TextareaType::class, [
                'attr' => ['class' => 'form-control content-editor'],
                'label' => 'Treść strony',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => StaticPage::class,
        ]);
    }
}
