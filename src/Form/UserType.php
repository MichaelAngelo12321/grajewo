<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\User;
use App\Validator\Constraints\UniqueEmail;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;

class UserType extends AbstractType
{
    public function __construct(
        private PasswordHasherFactoryInterface $passwordHasherFactory,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'constraints' => [
                    new Email([
                        'message' => 'Podany adres e-mail jest nieprawidłowy',
                    ]),
                    new UniqueEmail(['payload' => $options['is_edit'] ? $options['data']->getEmail() : null]),
                ],
                'label' => 'Adres e-mail',
            ])
            ->add('plainPassword', PasswordType::class, [
                'constraints' => !$options['is_edit']
                    ? [
                        new Length([
                            'min' => 8,
                            'minMessage' => 'Hasło powinno zawierać co najmniej {{ limit }} znaków',
                        ]),
                        new Regex([
                            'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{8,}$/',
                            'message' => 'Hasło powinno zawierać co najmniej jedną małą literę, jedną wielką literę i jedną cyfrę',
                        ]),
                    ] : [],
                'empty_data' => '',
                'help' => $options['is_edit'] ? 'Pozostaw puste, jeśli nie chcesz zmienić' : null,
                'label' => 'Hasło',
                'mapped' => false,
                'required' => !$options['is_edit'],
            ])
            ->add('fullName', null, [
                'label' => 'Pełna nazwa użytkownika',
            ])
            ->add('position', null, [
                'label' => 'Stanowisko',
            ])
            ->add('image', FileType::class, [
                'attr' => [
                    'accept' => 'image/jpeg',
                ],
                'help' => $options['is_edit'] ? 'Pozostaw puste, jeśli nie chcesz zmienić' : null,
                'label' => 'Zdjęcie',
                'mapped' => false,
                'required' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => '<i class="ti ti-check me-1"></i> Zapisz użytkownika',
                'label_html' => true,
                'row_attr' => [
                    'class' => 'd-flex justify-content-end',
                ],
            ])
            ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($options) {
                /** @var User $user */
                $user = $event->getData();
                $form = $event->getForm();

                if (!$options['is_edit']) {
                    $user->setIsActive(true);
                    $user->setPassword(
                        $this->passwordHasherFactory->getPasswordHasher($user)->hash(
                            $form->get('plainPassword')->getData(),
                        ),
                    );
                    $user->setRoles(['ROLE_ADMIN']);
                } elseif (!empty($form->get('plainPassword')->getData())) {
                    $user->setPassword(
                        $this->passwordHasherFactory->getPasswordHasher($user)->hash(
                            $form->get('plainPassword')->getData(),
                        ),
                    );
                }
            });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_edit' => false,
            'validation_groups' => ['Default', 'user_create'],
        ]);
    }
}
