<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Meal;
use App\Entity\MealPlan;
use App\Entity\Restaurant;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CorrectFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $restaurant1 = new Restaurant();
        $restaurant1->setName('The Golden Spoon');
        $manager->persist($restaurant1);

        $restaurant2 = new Restaurant();
        $restaurant2->setName('Green Garden Eatery');
        $manager->persist($restaurant2);

        $restaurant3 = new Restaurant();
        $restaurant3->setName('Spice Route Bistro');
        $manager->persist($restaurant3);

        $restaurant4 = new Restaurant();
        $restaurant4->setName('Ocean Fresh Seafood');
        $manager->persist($restaurant4);

        $categoryNames = ['Italian', 'Mexican', 'Asian', 'Vegan', 'Desserts', 'American', 'Breakfast'];
        $categories = [];

        foreach ($categoryNames as $name) {
            $category = new Category();
            $category->setName($name);
            $manager->persist($category);
            $categories[$name] = $category;
        }

        $restaurant1->addCategory($categories['Italian']);
        $restaurant1->addCategory($categories['Vegan']);

        $restaurant2->addCategory($categories['Mexican']);
        $restaurant2->addCategory($categories['American']);

        $restaurant3->addCategory($categories['Asian']);
        $restaurant3->addCategory($categories['Breakfast']);

        $restaurant4->addCategory($categories['Desserts']);

        $mealsData = [
            ['name' => 'Spaghetti Carbonara', 'description' => 'Classic Italian pasta dish with eggs, cheese, pancetta, and black pepper.', 'price' => 1000, 'category' => 'Italian', 'image' => 'pasta.jpg', 'calories' => 600, 'protein' => 25, 'fat' => 30, 'carbs' => 50, 'restaurant' => $restaurant1],
            ['name' => 'Chicken Fajitas', 'description' => 'Sizzling grilled chicken with onions and peppers, served with tortillas.', 'price' => 1100, 'category' => 'Mexican', 'image' => 'fajitas.jpg', 'calories' => 800, 'protein' => 40, 'fat' => 35, 'carbs' => 60, 'restaurant' => $restaurant2],
            ['name' => 'Sushi Platter', 'description' => 'Assortment of fresh sushi and sashimi.', 'price' => 1200, 'category' => 'Asian', 'image' => 'sushi.jpg', 'calories' => 500, 'protein' => 30, 'fat' => 15, 'carbs' => 60, 'restaurant' => $restaurant3],
            ['name' => 'Lentil Shepherd\'s Pie', 'description' => 'Hearty vegan shepherd\'s pie with a lentil filling and mashed potato topping.', 'price' => 1400, 'category' => 'Vegan', 'image' => 'shepherdspie.jpg', 'calories' => 700, 'protein' => 30, 'fat' => 25, 'carbs' => 80, 'restaurant' => $restaurant1],
            ['name' => 'Chocolate Lava Cake', 'description' => 'Warm chocolate cake with a gooey molten center.', 'price' => 900, 'category' => 'Desserts', 'image' => 'lavacake.jpg', 'calories' => 450, 'protein' => 5, 'fat' => 25, 'carbs' => 50, 'restaurant' => $restaurant4],
            ['name' => 'Classic Burger', 'description' => 'Juicy beef patty with lettuce, tomato, and cheese.', 'price' => 1300, 'category' => 'American', 'image' => 'burger.jpg', 'calories' => 900, 'protein' => 45, 'fat' => 50, 'carbs' => 40, 'restaurant' => $restaurant2],
            ['name' => 'Pancakes with Maple Syrup', 'description' => 'Fluffy pancakes served with butter and maple syrup.', 'price' => 950, 'category' => 'Breakfast', 'image' => 'pancakes.jpg', 'calories' => 600, 'protein' => 10, 'fat' => 20, 'carbs' => 90, 'restaurant' => $restaurant3],
            ['name' => 'Fish and Chips', 'description' => 'Crispy battered fish with golden fries.', 'price' => 1000, 'category' => 'American', 'image' => 'fish_chips.jpg', 'calories' => 1100, 'protein' => 50, 'fat' => 60, 'carbs' => 80, 'restaurant' => $restaurant4],
        ];

        $meals = [];
        foreach ($mealsData as $mealData) {
            $meal = new Meal();
            $meal->setName($mealData['name']);
            $meal->setDescription($mealData['description']);
            $meal->setPrice($mealData['price']);
            $meal->setCalories($mealData['calories']);
            $meal->setProtein($mealData['protein']);
            $meal->setFat($mealData['fat']);
            $meal->setCarbs($mealData['carbs']);
            $meal->setRestaurant($mealData['restaurant']);

            $manager->persist($meal);
            $meals[] = $meal;
        }

        // Meal Plans
        $mealPlan1 = new MealPlan();
        $mealPlan1->setName('Italian Feast');
        $mealPlan1->setDescription('A delicious selection of Italian dishes.');
        $mealPlan1->setRestaurant($restaurant1);
        $mealPlan1->addMeal($meals[0]); // Spaghetti Carbonara
        $mealPlan1->addCategory($categories['Italian']);
        $manager->persist($mealPlan1);

        $mealPlan2 = new MealPlan();
        $mealPlan2->setName('Healthy Vegan Week');
        $mealPlan2->setDescription('Nutritious and delicious vegan meals for the week.');
        $mealPlan2->setRestaurant($restaurant1);
        $mealPlan2->addMeal($meals[3]); // Lentil Shepherd's Pie
        $mealPlan2->addCategory($categories['Vegan']);
        $manager->persist($mealPlan2);

        $mealPlan3 = new MealPlan();
        $mealPlan3->setName('Mexican Fiesta');
        $mealPlan3->setDescription('Spicy and flavorful Mexican dishes.');
        $mealPlan3->setRestaurant($restaurant2);
        $mealPlan3->addMeal($meals[1]); // Chicken Fajitas
        $mealPlan3->addCategory($categories['Mexican']);
        $manager->persist($mealPlan3);

        $mealPlan4 = new MealPlan();
        $mealPlan4->setName('Asian Delights');
        $mealPlan4->setDescription('A taste of Asia with fresh and vibrant flavors.');
        $mealPlan4->setRestaurant($restaurant3);
        $mealPlan4->addMeal($meals[2]); // Sushi Platter
        $mealPlan4->addCategory($categories['Asian']);
        $manager->persist($mealPlan4);

        $manager->flush();
    }
}
