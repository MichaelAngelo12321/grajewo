<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\ArticleComment;
use Karser\Recaptcha3Bundle\Form\Recaptcha3Type;
use Karser\Recaptcha3Bundle\Validator\Constraints\Recaptcha3;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommentType extends AbstractType
{
    public function __construct(
        private bool $recaptchaEnabled,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('author', null, [
                'label' => 'Autor (opcjonalnie)',
                'required' => false,
            ])
            ->add('content', null, [
                'label' => 'Treść komentarza',
            ]);

        if ($this->recaptchaEnabled) {
            $builder->add('captcha', Recaptcha3Type::class, [
                'constraints' => new Recaptcha3(),
                'action_name' => 'article_comment',
            ]);
        }

        $builder->add('submit', SubmitType::class, [
                'label' => 'Dodaj komentarz',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ArticleComment::class,
        ]);
    }
}
