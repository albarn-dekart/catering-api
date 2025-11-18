<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ImageController extends AbstractController
{
    #[Route('/api/images/{folder}/{filename}', name: 'serve_image', requirements: ['filename' => '.+'])]
    public function serveImage(string $folder, string $filename): BinaryFileResponse
    {
        $path = $this->getParameter('images_directory') . "/$folder/$filename";

        if (!file_exists($path)) {
            throw new NotFoundHttpException("Image not found.");
        }

        $response = new BinaryFileResponse($path);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $filename);

        return $response;
    }
}
