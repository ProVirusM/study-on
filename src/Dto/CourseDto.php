<?php


namespace App\Dto;

use App\Repository\CourseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\UniqueCourseCode;

#[ORM\Entity(repositoryClass: CourseRepository::class)]
class CourseDto
{

    private ?int $id = null;

    #[Assert\NotBlank(message: 'Пожалуйста, введите код курса.')]
    #[Assert\Length(max: 255, maxMessage: 'Код курса не может быть длиннее {{ limit }}')]
    #[UniqueCourseCode]
    private ?string $code = null;

    #[Assert\NotBlank(message: 'Пожалуйста, введите код курса.')]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: "Код курса должен содержать минимум {{ min }} символа.",
        maxMessage: "Код курса должен содержать максимум {{ max }} символов."
    )]
    private ?string $title = null;


    #[Assert\NotBlank(message: 'Пожалуйста, введите код курса.')]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: "Название должно содержать минимум {{ min }} символа.",
        maxMessage: "Название должно содержать максимум {{ max }} символов."
    )]
    private ?string $description = null;

    private string $type;
    private ?float $price;



    public function getId(): ?int
    {
        return $this->id;
    }
    public function setId(?int $id): CourseDto
    {
        $this->id = $id;
        return $this;
    }
    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): CourseDto
    {
        $this->code = $code;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): CourseDto
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): CourseDto
    {
        $this->description = $description;

        return $this;
    }
    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(?float $price): CourseDto
    {
        $this->price = $price;
        return $this;
    }
    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): CourseDto
    {
        $this->type = $type;
        return $this;
    }




}
