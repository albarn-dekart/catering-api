<?php

namespace App\DataFixtures;

use App\Entity\DietCategory;
use App\Entity\Meal;
use App\Entity\MealPlan;
use App\Entity\Restaurant;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class MealPlanFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Italian Restaurant Meal Plans
        $italianPlans = [
            [
                'name' => 'Classic Italian Week',
                'desc' => 'A week of traditional Italian favorites',
                'meals' => ['italian_meal_0', 'italian_meal_1', 'italian_meal_2', 'italian_meal_5', 'italian_meal_6'],
                'categories' => [DietCategoryFixtures::HIGH_PROTEIN],
            ],
            [
                'name' => 'Vegetarian Italian',
                'desc' => 'Meat-free Italian delights',
                'meals' => ['italian_meal_0', 'italian_meal_3', 'italian_meal_4', 'italian_meal_5'],
                'categories' => [DietCategoryFixtures::VEGETARIAN],
            ],
            [
                'name' => 'Italian Pasta Lovers',
                'desc' => 'Best pasta dishes from our kitchen',
                'meals' => ['italian_meal_1', 'italian_meal_4', 'italian_meal_5', 'italian_meal_9'],
                'categories' => [],
            ],
        ];

        $this->createMealPlansForRestaurant($manager, RestaurantFixtures::RESTAURANT_1, $italianPlans, 'italian_plan');

        // Asian Restaurant Meal Plans
        $asianPlans = [
            [
                'name' => 'Asian Fusion Weekly',
                'desc' => 'Mix of Thai, Japanese, and Chinese favorites',
                'meals' => ['asian_meal_0', 'asian_meal_1', 'asian_meal_4', 'asian_meal_7', 'asian_meal_9'],
                'categories' => [DietCategoryFixtures::HIGH_PROTEIN, DietCategoryFixtures::DAIRY_FREE],
            ],
            [
                'name' => 'Sushi & Ramen Special',
                'desc' => 'Japanese specialties for sushi and ramen lovers',
                'meals' => ['asian_meal_2', 'asian_meal_7', 'asian_meal_4'],
                'categories' => [DietCategoryFixtures::HIGH_PROTEIN],
            ],
            [
                'name' => 'Vegetarian Asian',
                'desc' => 'Plant-based Asian cuisine',
                'meals' => ['asian_meal_3', 'asian_meal_5', 'asian_meal_8'],
                'categories' => [DietCategoryFixtures::VEGETARIAN, DietCategoryFixtures::VEGAN],
            ],
        ];

        $this->createMealPlansForRestaurant($manager, RestaurantFixtures::RESTAURANT_2, $asianPlans, 'asian_plan');

        // Healthy Bowl Meal Plans
        $healthyPlans = [
            [
                'name' => 'Keto Power Week',
                'desc' => 'Low-carb, high-fat meals for ketogenic diet',
                'meals' => ['healthy_meal_2', 'healthy_meal_3', 'healthy_meal_6'],
                'categories' => [DietCategoryFixtures::KETO, DietCategoryFixtures::LOW_CARB, DietCategoryFixtures::GLUTEN_FREE],
            ],
            [
                'name' => 'Vegan Complete',
                'desc' => 'Fully plant-based nutrition plan',
                'meals' => ['healthy_meal_4', 'healthy_meal_8', 'healthy_meal_9'],
                'categories' => [DietCategoryFixtures::VEGAN, DietCategoryFixtures::VEGETARIAN, DietCategoryFixtures::DAIRY_FREE],
            ],
            [
                'name' => 'High Protein Athlete',
                'desc' => 'Protein-packed meals for active lifestyles',
                'meals' => ['healthy_meal_0', 'healthy_meal_2', 'healthy_meal_6', 'healthy_meal_7'],
                'categories' => [DietCategoryFixtures::HIGH_PROTEIN, DietCategoryFixtures::GLUTEN_FREE],
            ],
            [
                'name' => 'Paleo Clean Eating',
                'desc' => 'Paleo-friendly whole food meals',
                'meals' => ['healthy_meal_0', 'healthy_meal_1', 'healthy_meal_2'],
                'categories' => [DietCategoryFixtures::PALEO, DietCategoryFixtures::GLUTEN_FREE, DietCategoryFixtures::DAIRY_FREE],
            ],
        ];

        $this->createMealPlansForRestaurant($manager, RestaurantFixtures::RESTAURANT_3, $healthyPlans, 'healthy_plan');

        // BBQ Restaurant Meal Plans
        $bbqPlans = [
            [
                'name' => 'BBQ Feast',
                'desc' => 'Ultimate BBQ experience with all the classics',
                'meals' => ['bbq_meal_0', 'bbq_meal_1', 'bbq_meal_2', 'bbq_meal_4', 'bbq_meal_8'],
                'categories' => [DietCategoryFixtures::HIGH_PROTEIN],
            ],
            [
                'name' => 'Smokehouse Special',
                'desc' => 'Best smoked meats and sides',
                'meals' => ['bbq_meal_1', 'bbq_meal_3', 'bbq_meal_7'],
                'categories' => [DietCategoryFixtures::HIGH_PROTEIN, DietCategoryFixtures::GLUTEN_FREE],
            ],
        ];

        $this->createMealPlansForRestaurant($manager, RestaurantFixtures::RESTAURANT_4, $bbqPlans, 'bbq_plan');

        // Fast Food Meal Plans
        $fastFoodPlans = [
            [
                'name' => 'Quick Lunch Combo',
                'desc' => 'Fast and satisfying lunch options',
                'meals' => ['fastfood_meal_0', 'fastfood_meal_2', 'fastfood_meal_8'],
                'categories' => [],
            ],
            [
                'name' => 'Family Meal Deal',
                'desc' => 'Perfect for family dinners',
                'meals' => ['fastfood_meal_0', 'fastfood_meal_1', 'fastfood_meal_2', 'fastfood_meal_6', 'fastfood_meal_8'],
                'categories' => [],
            ],
            [
                'name' => 'Vegetarian Fast Food',
                'desc' => 'Meat-free quick meal options',
                'meals' => ['fastfood_meal_5', 'fastfood_meal_9'],
                'categories' => [DietCategoryFixtures::VEGETARIAN],
            ],
        ];

        $this->createMealPlansForRestaurant($manager, RestaurantFixtures::RESTAURANT_5, $fastFoodPlans, 'fastfood_plan');

        $manager->flush();
    }

    private function createMealPlansForRestaurant(
        ObjectManager $manager,
        string $restaurantRef,
        array $plansData,
        string $referencePrefix
    ): void {
        /** @var Restaurant $restaurant */
        $restaurant = $this->getReference($restaurantRef, Restaurant::class);

        foreach ($plansData as $index => $data) {
            $mealPlan = new MealPlan();
            $mealPlan->setName($data['name']);
            $mealPlan->setDescription($data['desc']);
            $mealPlan->setRestaurant($restaurant);

            // Add meals to the plan
            foreach ($data['meals'] as $mealRef) {
                /** @var Meal $meal */
                $meal = $this->getReference($mealRef, Meal::class);
                $mealPlan->addMeal($meal);
            }

            // Add diet categories
            foreach ($data['categories'] as $categoryRef) {
                /** @var DietCategory $category */
                $category = $this->getReference($categoryRef, DietCategory::class);
                $mealPlan->addDietCategory($category);
            }

            $manager->persist($mealPlan);
            $this->addReference("{$referencePrefix}_$index", $mealPlan);
        }
    }

    public function getDependencies(): array
    {
        return [
            RestaurantFixtures::class,
            MealFixtures::class,
            DietCategoryFixtures::class,
        ];
    }
}
