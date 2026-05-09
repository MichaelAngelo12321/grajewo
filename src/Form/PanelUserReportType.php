<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\UserReport;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PanelUserReportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, [
                'label' => 'Tytuł raportu',
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Treść',
                'attr' => ['rows' => 6],
            ])
            ->add('author', null, [
                'label' => 'Autor',
                'required' => false,
            ])
            ->add('phone', null, [
                'label' => 'Telefon',
                'required' => false,
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Opublikowany',
                'required' => false,
            ])
            ->add('isHidden', CheckboxType::class, [
                'label' => 'Ukryty',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserReport::class,
        ]);
    }
}
