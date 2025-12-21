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

        $italianPlans = [
            [
                'name' => 'Klasyczny Włoski Tydzień',
                'desc' => 'Tydzień tradycyjnych włoskich przysmaków',
                'meals' => ['italian_meal_0', 'italian_meal_1', 'italian_meal_2', 'italian_meal_5', 'italian_meal_6'],
                'categories' => [DietCategoryFixtures::HIGH_PROTEIN],
                'image' => 'klas_wlo.webp',
            ],
            [
                'name' => 'Włoski Wegetariański',
                'desc' => 'Włoskie przysmaki bez mięsa',
                'meals' => ['italian_meal_0', 'italian_meal_3', 'italian_meal_4', 'italian_meal_5'],
                'categories' => [DietCategoryFixtures::VEGETARIAN],
                'image' => 'wlo_weg.webp',
            ],
            [
                'name' => 'Włoscy Miłośnicy Makaronów',
                'desc' => 'Najlepsze dania makaronowe z naszej kuchni',
                'meals' => ['italian_meal_1', 'italian_meal_4', 'italian_meal_5', 'italian_meal_9'],
                'categories' => [],
                'image' => 'makarony.webp',
            ],
        ];

        $this->createMealPlansForRestaurant($manager, RestaurantFixtures::RESTAURANT_1, $italianPlans, 'italian_plan');


        $asianPlans = [
            [
                'name' => 'Azjatycki Tydzień Smaków',
                'desc' => 'Podróż po smakach Tajlandii, Japonii i Chin',
                'meals' => ['asian_meal_0', 'asian_meal_1', 'asian_meal_4', 'asian_meal_5', 'asian_meal_6'],
                'categories' => [DietCategoryFixtures::LOW_CARB],
                'image' => 'lunch.webp',
            ],
            [
                'name' => 'Sushi i Zupy',
                'desc' => 'Wybór lekkich i pożywnych dań azjatyckich',
                'meals' => ['asian_meal_2', 'asian_meal_5', 'asian_meal_6', 'asian_meal_7'],
                'categories' => [DietCategoryFixtures::GLUTEN_FREE],
                'image' => 'sushi_zupy.webp',
            ],
            [
                'name' => 'Wegańska Azja',
                'desc' => 'Dania w 100% roślinne z kuchni azjatyckiej',
                'meals' => ['asian_meal_1', 'asian_meal_3', 'asian_meal_7', 'asian_meal_9'],
                'categories' => [DietCategoryFixtures::VEGAN],
                'image' => 'wegańska_azja.webp',
            ],
        ];

        $this->createMealPlansForRestaurant($manager, RestaurantFixtures::RESTAURANT_2, $asianPlans, 'asian_plan');


        $healthyPlans = [
            [
                'name' => 'Tydzień Zdrowej Diety',
                'desc' => 'Zbilansowane posiłki dla zachowania formy',
                'meals' => ['healthy_meal_0', 'healthy_meal_1', 'healthy_meal_6', 'healthy_meal_7', 'healthy_meal_8'],
                'categories' => [DietCategoryFixtures::PALEO, DietCategoryFixtures::LOW_CARB],
                'image' => 'zdrowa_dieta.webp',
            ],
            [
                'name' => 'Miski i Sałatki',
                'desc' => 'Lekkie i pełne witamin posiłki',
                'meals' => ['healthy_meal_0', 'healthy_meal_3', 'healthy_meal_4', 'healthy_meal_5'],
                'categories' => [DietCategoryFixtures::KETO],
                'image' => 'miski_sałatki.webp',
            ],
            [
                'name' => 'Wysokobiałkowe Fit',
                'desc' => 'Posiłki bogate w białko dla budujących mięśnie',
                'meals' => ['healthy_meal_1', 'healthy_meal_3', 'healthy_meal_9'],
                'categories' => [DietCategoryFixtures::HIGH_PROTEIN],
                'image' => 'białko_fit.webp',
            ],
            [
                'name' => 'Bezglutenowe Opcje',
                'desc' => 'Wybór posiłków bez glutenu',
                'meals' => ['healthy_meal_1', 'healthy_meal_2', 'healthy_meal_6', 'healthy_meal_8'],
                'categories' => [DietCategoryFixtures::GLUTEN_FREE],
                'image' => 'zdrowa_dieta.webp',
            ],
        ];

        $this->createMealPlansForRestaurant($manager, RestaurantFixtures::RESTAURANT_3, $healthyPlans, 'healthy_plan');


        $bbqPlans = [
            [
                'name' => 'Tydzień Amerykańskiego Grillowania',
                'desc' => 'Najlepsze wędzone mięsa i dania z grilla',
                'meals' => ['bbq_meal_0', 'bbq_meal_1', 'bbq_meal_3', 'bbq_meal_6', 'bbq_meal_7'],
                'categories' => [DietCategoryFixtures::HIGH_PROTEIN],
                'image' => 'grill.webp',
            ],
            [
                'name' => 'Klasyki Kuchni Southern',
                'desc' => 'Tradycyjne dania z południa Stanów Zjednoczonych',
                'meals' => ['bbq_meal_1', 'bbq_meal_4', 'bbq_meal_5', 'bbq_meal_8'],
                'categories' => [],
                'image' => 'kuchnia_southern.webp',
            ],
        ];

        $this->createMealPlansForRestaurant($manager, RestaurantFixtures::RESTAURANT_4, $bbqPlans, 'bbq_plan');


        $fastFoodPlans = [
            [
                'name' => 'Szybki Zestaw Lunchowy',
                'desc' => 'Idealny zestaw na szybki obiad',
                'meals' => ['fastfood_meal_0', 'fastfood_meal_3', 'fastfood_meal_7'],
                'categories' => [],
                'image' => 'lunch.webp',
            ],
            [
                'name' => 'Rodzinny Zestaw Burgerów',
                'desc' => 'Burgery i frytki dla całej rodziny',
                'meals' => ['fastfood_meal_1', 'fastfood_meal_3', 'fastfood_meal_7', 'fastfood_meal_9'],
                'categories' => [],
                'image' => 'zestaw_burgerów.webp',
            ],
            [
                'name' => 'Fit Fast Food',
                'desc' => 'Lżejsze opcje z fast food menu',
                'meals' => ['fastfood_meal_2', 'fastfood_meal_5', 'fastfood_meal_8'],
                'categories' => [DietCategoryFixtures::LOW_CARB],
                'image' => 'fit_fast_food.webp',
            ],
        ];

        $this->createMealPlansForRestaurant($manager, RestaurantFixtures::RESTAURANT_5, $fastFoodPlans, 'fastfood_plan');


        $polishPlans = [
            [
                'name' => 'Polskie Klasyki',
                'desc' => 'Zestaw najbardziej znanych polskich dań',
                'meals' => ['polish_meal_0', 'polish_meal_1', 'polish_meal_3', 'polish_meal_6', 'polish_meal_9'],
                'categories' => [DietCategoryFixtures::HIGH_PROTEIN],
                'image' => 'polskie_klasyki.webp',
            ],
            [
                'name' => 'Domowy Obiadek',
                'desc' => 'Poczuj się jak w domu u mamy',
                'meals' => ['polish_meal_1', 'polish_meal_2', 'polish_meal_5', 'polish_meal_7'],
                'categories' => [],
                'image' => 'domowy_obiadek.webp',
            ],
            [
                'name' => 'Polska Uczta',
                'desc' => 'Sycące dania na duży głód',
                'meals' => ['polish_meal_1', 'polish_meal_3', 'polish_meal_8', 'polish_meal_9'],
                'categories' => [DietCategoryFixtures::HIGH_PROTEIN],
                'image' => 'polska_uczta.webp',
            ],
        ];

        $this->createMealPlansForRestaurant($manager, RestaurantFixtures::RESTAURANT_6, $polishPlans, 'polish_plan');

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
            if (isset($data['image'])) {
                $mealPlan->setImagePath($data['image']);
            }


            foreach ($data['meals'] as $mealRef) {
                /** @var Meal $meal */
                $meal = $this->getReference($mealRef, Meal::class);
                $mealPlan->addMeal($meal);
            }


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
