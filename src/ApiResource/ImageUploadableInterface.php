<?php

namespace App\ApiResource;

use Symfony\Component\HttpFoundation\File\File;

interface ImageUploadableInterface
{
    public function setImageFile(?File $imageFile): static;

    public function getId(): ?int;
}
