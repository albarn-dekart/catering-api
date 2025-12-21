<?php

namespace App\Controller;

use App\ApiResource\ImageUploadableInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use App\Entity\Meal;
use App\Entity\MealPlan;
use App\Entity\Restaurant;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
class ImageUploadController extends AbstractController
{
    #[Route('/api/meals/{id}/image', name: 'meal_image_upload', defaults: ['_api_resource_class' => Meal::class], methods: ['POST'])]
    #[Route('/api/restaurants/{id}/image', name: 'restaurant_image_upload', defaults: ['_api_resource_class' => Restaurant::class], methods: ['POST'])]
    #[Route('/api/meal_plans/{id}/image', name: 'meal_plan_image_upload', defaults: ['_api_resource_class' => MealPlan::class], methods: ['POST'])]
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
    ): Response {
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

        return $this->json([
            'imageUrl' => $entity->getImageUrl(),
        ]);
    }
}
