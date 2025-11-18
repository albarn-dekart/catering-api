<?php

namespace App\Controller;

use App\ApiResource\ImageUploadableInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

#[AsController]
class ImageUploadController extends AbstractController
{
    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function __invoke(
        Request $request,
        EntityManagerInterface $entityManager,
        string $id,
        string $_api_resource_class,
        string $serializationGroup = null
    ): Response
    {
        $entity = $entityManager->find($_api_resource_class, $id);

        if (!$entity) {
            throw $this->createNotFoundException('Resource not found');
        }

        if (!$entity instanceof ImageUploadableInterface) {
            throw new BadRequestHttpException('Invalid resource type');
        }

        $uploadedFile = $request->files->get('file');

        if (!$uploadedFile) {
            throw new BadRequestHttpException('"file" is required');
        }

        $entity->setImageFile($uploadedFile);
        $entityManager->flush();

        $context = $serializationGroup ? ['groups' => $serializationGroup] : [];

        return $this->json($entity, context: $context);
    }
}
