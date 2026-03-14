<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\PromoItem;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Validator\Constraints\File;

class PromoItemType extends AbstractType
{
    public function __construct(private ParameterBagInterface $params)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $positions = [
            '-- wybierz --' => '',
        ];

        if ($this->params->has('promo_item_positions')) {
            $splitPositions = explode(',', $this->params->get('promo_item_positions'));

            foreach ($splitPositions as $item) {
                $positions[$item] = $item;
            }
        }


        $builder
            ->add('title', null, [
                'label' => 'Nazwa reklamy',
            ])
            ->add('description', null, [
                'label' => 'Opis reklamy',
            ])
            ->add('imageUrl', FileType::class, [
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
                'required' => true,
                'mapped' => false,
                'attr' => [
                    'accept' => 'image/jpeg,image/png,image/webp',
                    'data-max-size' => 2 * 1024 * 1024,
                ],
            ])
            ->add('targetUrl', UrlType::class, [
                'label' => 'URL docelowy',
                'required' => false,
            ])
            ->add('isActive', null, [
                'label' => 'Reklama aktywna',
            ])
            ->add('startDate', DateType::class, [
                'label' => 'Data rozpoczęcia publikacji',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('endDate', DateType::class, [
                'label' => 'Data zakończenia publikacji',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('position', ChoiceType::class, [
                'label' => 'Slot reklamowy',
                'choices' => $positions,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PromoItem::class,
        ]);
    }
}
