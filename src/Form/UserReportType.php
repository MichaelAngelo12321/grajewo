<?php

namespace App\Form;

use App\Entity\UserReport;
use Karser\Recaptcha3Bundle\Form\Recaptcha3Type;
use Karser\Recaptcha3Bundle\Validator\Constraints\Recaptcha3;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class UserReportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, [
                'attr' => [
                    'class' => 'form-control-lg',
                ],
                'label' => 'Tytuł raportu (opcjonalnie)',
                'required' => false,
            ])
            ->add('content', null, [
                'attr' => [
                    'rows' => 5,
                ],
                'label' => 'Napisz coś więcej',
            ])
            ->add('image', FileType::class, [
                'constraints' => [
                    new File([
                        'maxSize' => '12m',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                        ],
                        'mimeTypesMessage' => 'Wybierz dozwolony typ grafiki (jpg, png, gif)',
                    ])
                ],
                'label' => 'Zdjęcie (opcjonalnie)',
                'mapped' => false,
                'required' => false,
            ])
            ->add('author', null, [
                'label' => 'Twój podpis (opcjonalnie)',
                'required' => false,
            ])
            ->add('captcha', Recaptcha3Type::class, [
                'constraints' => new Recaptcha3(),
                'action_name' => 'user_report',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Wyślij raport',
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
