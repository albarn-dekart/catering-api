<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ImageController extends AbstractController
{
    #[Route('api/images/{filename}', name: 'serve_image', methods: ['GET'])]
    public function serveImage(string $filename): Response
    {
        $imagePath = $this->getParameter('images_directory') . '/' . $filename;

        if (!file_exists($imagePath)) {
            throw $this->createNotFoundException('Image not found');
        }

        // Return the image file directly
        return new BinaryFileResponse($imagePath);
    }
}