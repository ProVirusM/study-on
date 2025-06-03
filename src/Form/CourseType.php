<?php

namespace App\Form;

use App\Entity\Course;
use App\Repository\CourseRepository;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
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
            ->add('code', TextType::class)
            ->add('title', TextType::class)
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Бесплатный' => 'free',
                    'Аренда' => 'rent',
                    'Платный' => 'buy',
                ],
                'label' => 'Тип курса'
            ])
            ->add('price', MoneyType::class, [
                'help' => 'Если курс бесплатный, то введенная цена не будет учитываться',
                'label' => 'Цена курса',
                'scale' => 2,
                'currency' => 'RUB',
                'required' => false,
            ])
            ->add('description', TextareaType::class, [
                'required' => false,

            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => \App\Dto\CourseDto::class,
        ]);
    }
}
