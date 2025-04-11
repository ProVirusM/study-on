<?php

namespace App\Form;

use App\Entity\Course;
use App\Repository\CourseRepository;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class CourseType extends AbstractType
{
    private CourseRepository $courseRepository;
    public function __construct(CourseRepository $courseRepository)
    {
        $this->courseRepository = $courseRepository;
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Пожалуйста, введите код курса.'
                    ]),
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'Код курса не может быть длиннее {{ limit }}'
                    ]),
                    new Callback(function ($value, ExecutionContextInterface $context) {
                        $form = $context->getRoot();
                        $course = $form->getData();
                        $existingCourse = $this->courseRepository->findOneBy(['code' => $value]);

                        if ($existingCourse && $existingCourse->getId() !== $course->getId()) {
                            $context->buildViolation('Курс с таким кодом уже существует')
                                ->atPath('code')
                                ->addViolation();
                        }
                    }),
                ],
            ])
            ->add('title', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Пожалуйста, введите название курса',
                    ]),
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'Название курса не может быть длиннее {{ limit }} символов',
                    ]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 1000,
                        'maxMessage' => 'Описание курса не может быть длиннее {{ limit }} символов',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Course::class,
        ]);
    }
}
