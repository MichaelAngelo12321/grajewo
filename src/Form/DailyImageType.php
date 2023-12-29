<?php

namespace App\Form;

use App\Entity\DailyImage;
use Karser\Recaptcha3Bundle\Form\Recaptcha3Type;
use Karser\Recaptcha3Bundle\Validator\Constraints\Recaptcha3;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class DailyImageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
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
                'label' => 'Zdjęcie',
                'mapped' => false,
            ])
            ->add('author', null, [
                'label' => 'Autor (opcjonalnie)',
                'required' => false,
            ])
            ->add('caption', null, [
                'label' => 'Opis zdjęcia (opcjonalnie)',
                'required' => false,
            ])
            ->add('captcha', Recaptcha3Type::class, [
                'constraints' => new Recaptcha3(),
                'action_name' => 'daily_image',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Prześlij zdjęcie',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DailyImage::class,
        ]);
    }
}
