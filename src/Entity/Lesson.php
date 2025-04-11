<?php

namespace App\Entity;

use App\Repository\LessonRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
#[ORM\Entity(repositoryClass: LessonRepository::class)]
class Lesson
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'lessons')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Course $course = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Название урока не может быть пустым.")]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: "Название урока должно содержать минимум {{ min }} символа.",
        maxMessage: "Название урока должно содержать максимум {{ max }} символов."
    )]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: "Описание не может быть пустым.")]
    #[Assert\Length(
        min: 3,
        max: 1000,
        minMessage: "Описание должно содержать минимум {{ min }} символа.",
        maxMessage: "Описание должно содержать максимум {{ max }} символов."
    )]
    private ?string $content = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 1, max: 10000, notInRangeMessage: 'Цена курса не должна быть меньше {{ min }} и не должна превышать {{ max }} рублей.')]
    private ?int $orderNumber = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCourse(): ?Course
    {
        return $this->course;
    }

    public function setCourse(?Course $course): static
    {
        $this->course = $course;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getOrderNumber(): ?int
    {
        return $this->orderNumber;
    }

    public function setOrderNumber(?int $orderNumber): static
    {
        $this->orderNumber = $orderNumber;

        return $this;
    }
}
