<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Company;
use App\Entity\CompanyCategory;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

class CompanyFrontendType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nazwa firmy',
                'constraints' => [
                    new NotBlank(['message' => 'Proszę podać nazwę firmy.']),
                ],
            ])
            ->add('category', EntityType::class, [
                'class' => CompanyCategory::class,
                'choice_label' => 'name',
                'label' => 'Kategoria',
                'placeholder' => 'Wybierz kategorię',
                'constraints' => [
                    new NotBlank(['message' => 'Proszę wybrać kategorię.']),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Opis firmy',
                'attr' => ['rows' => 5],
                'required' => false,
            ])
            ->add('address', TextType::class, [
                'label' => 'Adres (ulica i numer)',
                'required' => false,
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'Kod pocztowy',
                'required' => false,
            ])
            ->add('city', TextType::class, [
                'label' => 'Miasto',
                'required' => false,
            ])
            ->add('phone', TelType::class, [
                'label' => 'Telefon',
                'required' => false,
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => false,
            ])
            ->add('website', UrlType::class, [
                'label' => 'Strona WWW',
                'required' => false,
            ])
            ->add('logo', FileType::class, [
                'label' => 'Logo firmy',
                'mapped' => false,
                'required' => false,
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
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Company::class,
        ]);
    }
}
