<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\DailyVideo;
use Karser\Recaptcha3Bundle\Form\Recaptcha3Type;
use Karser\Recaptcha3Bundle\Validator\Constraints\Recaptcha3;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Url;

class DailyVideoType extends AbstractType
{
    public function __construct(
        private bool $recaptchaEnabled,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('videoUrl', UrlType::class, [
                'constraints' => [
                    new Regex([
                        'pattern' => '/^(https?:\/\/)?(www\.)?(youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9\-_]+)(\&\S+)?$/',
                        'message' => 'Niepoprawny adres URL filmu.',
                    ]),
                ],
                'label' => 'Adres URL filmu z YouTube',
            ])
            ->add('author', null, [
                'label' => 'Autor (opcjonalnie)',
                'required' => false,
            ])
            ->add('caption', null, [
                'label' => 'Opis filmu (opcjonalnie)',
                'required' => false,
            ]);

        if ($this->recaptchaEnabled) {
            $builder->add('captcha', Recaptcha3Type::class, [
                'constraints' => new Recaptcha3(),
                'action_name' => 'daily_video',
            ]);
        }

        $builder->add('submit', SubmitType::class, [
                'label' => 'Prześlij film',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DailyVideo::class,
        ]);
    }
}
