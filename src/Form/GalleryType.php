<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Gallery;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\File;

class GalleryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nazwa galerii',
            ])
            ->add('images', FileType::class, [
                'label' => 'Zdjęcia',
                'multiple' => true,
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new All([
                        'constraints' => [
                            new File([
                                'maxSize' => '2048k',
                                'mimeTypes' => [
                                    'image/jpeg',
                                    'image/png',
                                    'image/webp',
                                ],
                                'mimeTypesMessage' => 'Proszę przesłać poprawny obrazek (JPG, PNG, WEBP)',
                            ])
                        ],
                    ]),
                ],
                'attr' => [
                    'accept' => 'image/jpeg,image/png,image/webp',
                    'data-max-files' => 50,
                    'data-max-size' => 2 * 1024 * 1024,
                ],
            ])
            ->add('imageOrder', HiddenType::class, [
                'mapped' => false,
                'required' => false,
            ])
            ->add('removedImages', HiddenType::class, [
                'mapped' => false,
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Gallery::class,
        ]);
    }
}
