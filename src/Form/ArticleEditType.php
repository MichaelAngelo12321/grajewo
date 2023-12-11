<?php

namespace App\Form;

use App\Entity\Article;
use App\Entity\Category;
use App\Enum\ArticleStatus;
use DateTimeImmutable;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ArticleEditType extends AbstractType
{
    public function __construct(private Security $security)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, [
                'label' => 'Tytuł artykułu',
            ])
            ->add('category', EntityType::class, [
                'choice_filter' => fn(?Category $category) => !$category?->isIsRoot(),
                'class' => 'App\Entity\Category',
                'label' => 'Kategoria',
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Pełna treść artykułu',
            ])
            ->add('excerpt', TextareaType::class, [
                'label' => 'Zajawka artykułu',
                'help' => 'Jeśli pozostawisz puste, zostaną użyte 3 pierwsze zdania pełnej treści artykułu',
                'required' => false,
            ])
            ->add('hasChangedImageUrl', CheckboxType::class, [
                'mapped' => false,
                'required' => false,
            ])
            ->add('imageUrl', FileType::class, [
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
                'required' => false,
            ])
            ->add('imageCaption', TextType::class, [
                'label' => 'Podpis pod zdjęciem',
                'required' => false,
            ])
            ->add('hasCommentsDisabled', CheckboxType::class, [
                'label' => 'Wyłącz komentarze',
                'required' => false,
            ])
            ->add('isEvent', CheckboxType::class, [
                'label' => 'Oznacz jako wydarzenie',
                'required' => false,
            ])
            ->add('eventDate', TextType::class, [
                'label' => 'Data',
                'mapped' => false,
            ])
            ->add('eventTime', TextType::class, [
                'label' => 'Godzina',
                'mapped' => false,
            ])
            ->add('eventPlace', TextType::class, [
                'label' => 'Lokalizacja',
                'required' => false,
            ])
            ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
                /** @var Article $article */
                $article = $event->getData();
                $form = $event->getForm();

                $article->setAuthor($this->security->getUser());
                $article->setUpdateAuthor($this->security->getUser());

                if (
                    $form->get('isEvent')->getData() === true
                    && $form->get('eventDate')->getData() !== null
                ) {
                    $eventDate = $form->get('eventDate')->getData();
                    $eventTime = $form->get('eventTime')->getData();
                    $eventDateTime = $eventDate . ' ' . ($eventTime ?? '00:00');

                    $article->setEventDateTime(
                        DateTimeImmutable::createFromFormat('Y-m-d H:i', $eventDateTime),
                    );
                }

                if ($form->get('excerpt')->getData() === null) {
                    $excerpt = implode(
                        '. ',
                        array_slice(
                            explode('. ', $article->getContent()),
                            0,
                            3,
                        ),
                    );

                    if (strlen($excerpt) > 300) {
                        $excerpt = substr($excerpt, 0, 297) . '...';
                    }

                    $article->setExcerpt($excerpt);
                }
            });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Article::class,
        ]);
    }
}
