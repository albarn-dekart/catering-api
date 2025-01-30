<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ImageController
{
    private ParameterBagInterface $params;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
    }

    #[Route('/api/images/{filename}', name: 'api_get_images', methods: ['GET'])]
    public function serveImage(string $filename): Response
    {
        $filePath = $this->params->get('kernel.project_dir') . '/public/images/' . $filename;

        if (!file_exists($filePath)) {
            return new Response('File not found.', 404);
        }

        return new Response(file_get_contents($filePath), 200, ['Content-Type' => mime_content_type($filePath)]);
    }
}