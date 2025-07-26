<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class ImageController extends AbstractController
{
    #[Route('/images/{filename}', name: 'serve_image', methods: ['GET'])]
    public function serveImage(string $filename): Response
    {
        $imagePath = $this->getParameter('images_directory') . '/' . $filename;

        if (str_contains($filename, '..') || str_contains($filename, '/')) {
            throw $this->createNotFoundException('Invalid filename.');
        }

        if (!file_exists($imagePath)) {
            throw $this->createNotFoundException('Image not found.');
        }

        return new BinaryFileResponse($imagePath);
    }
}