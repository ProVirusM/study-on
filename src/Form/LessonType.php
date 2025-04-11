<?php

namespace App\Form;

use App\Entity\Course;
use App\Entity\Lesson;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Form\CallbackTransformer;
use Doctrine\ORM\EntityManagerInterface;

class LessonType extends AbstractType
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Пожалуйста, введите название урока',
                    ]),
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'Название урока не может быть длиннее {{ limit }} символов',
                    ]),
                ],
            ])
            ->add('content', TextareaType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Пожалуйста, введите содержание урока',
                    ]),
                ],
            ])
            ->add('orderNumber', IntegerType::class, [
                'required' => false,
                'constraints' => [
                    new Range([
                        'min' => 1,
                        'max' => 10000,
                        'notInRangeMessage' => 'Порядковый номер должен быть между {{ min }} и {{ max }}',
                    ]),
                ],
            ])
            ->add('course', HiddenType::class, [
                'data' => $options['course'] ? $options['course']->getId() : null,
                'mapped' => false,
                'constraints' => $options['require_course'] ? [
//                    new NotNull([
//                        'message' => 'Курс не указан',
//                    ]),
                ] : [],
            ])

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Lesson::class,
            'course' => null,
            'require_course' => true,
        ]);
    }
}
