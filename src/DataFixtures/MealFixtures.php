<?php

namespace App\DataFixtures;

use App\Entity\Meal;
use App\Entity\Restaurant;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class MealFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Italian Restaurant Meals
        $italianMeals = [
            ['name' => 'Margherita Pizza', 'desc' => 'Classic pizza with tomato, mozzarella, and fresh basil', 'price' => 1200, 'cal' => 650, 'protein' => 25, 'fat' => 22, 'carbs' => 85],
            ['name' => 'Spaghetti Carbonara', 'desc' => 'Creamy pasta with bacon, eggs, and parmesan', 'price' => 1400, 'cal' => 720, 'protein' => 28, 'fat' => 32, 'carbs' => 75],
            ['name' => 'Lasagna Bolognese', 'desc' => 'Layered pasta with meat sauce and béchamel', 'price' => 1500, 'cal' => 850, 'protein' => 35, 'fat' => 38, 'carbs' => 90],
            ['name' => 'Caprese Salad', 'desc' => 'Fresh mozzarella, tomatoes, and basil with olive oil', 'price' => 900, 'cal' => 320, 'protein' => 18, 'fat' => 24, 'carbs' => 12],
            ['name' => 'Risotto ai Funghi', 'desc' => 'Creamy mushroom risotto with parmesan', 'price' => 1300, 'cal' => 580, 'protein' => 15, 'fat' => 18, 'carbs' => 82],
            ['name' => 'Penne Arrabbiata', 'desc' => 'Spicy tomato pasta with garlic and chili', 'price' => 1100, 'cal' => 520, 'protein' => 16, 'fat' => 12, 'carbs' => 88],
            ['name' => 'Chicken Parmigiana', 'desc' => 'Breaded chicken with marinara and mozzarella', 'price' => 1600, 'cal' => 780, 'protein' => 42, 'fat' => 35, 'carbs' => 65],
            ['name' => 'Bruschetta', 'desc' => 'Toasted bread with tomatoes, garlic, and olive oil', 'price' => 700, 'cal' => 280, 'protein' => 8, 'fat' => 14, 'carbs' => 32],
            ['name' => 'Tiramisu', 'desc' => 'Classic Italian dessert with coffee and mascarpone', 'price' => 800, 'cal' => 450, 'protein' => 9, 'fat' => 24, 'carbs' => 52],
            ['name' => 'Minestrone Soup', 'desc' => 'Hearty vegetable soup with beans and pasta', 'price' => 950, 'cal' => 420, 'protein' => 14, 'fat' => 8, 'carbs' => 68],
        ];

        $this->createMealsForRestaurant($manager, RestaurantFixtures::RESTAURANT_1, $italianMeals, 'italian_meal');

        // Asian Restaurant Meals
        $asianMeals = [
            ['name' => 'Pad Thai', 'desc' => 'Stir-fried rice noodles with shrimp, peanuts, and tamarind', 'price' => 1350, 'cal' => 680, 'protein' => 24, 'fat' => 18, 'carbs' => 95],
            ['name' => 'Kung Pao Chicken', 'desc' => 'Spicy stir-fried chicken with peanuts and vegetables', 'price' => 1400, 'cal' => 620, 'protein' => 32, 'fat' => 26, 'carbs' => 58],
            ['name' => 'Sushi Platter', 'desc' => 'Assorted nigiri and maki rolls with fresh fish', 'price' => 1800, 'cal' => 480, 'protein' => 28, 'fat' => 12, 'carbs' => 64],
            ['name' => 'Tom Yum Soup', 'desc' => 'Spicy and sour Thai soup with shrimp and mushrooms', 'price' => 1100, 'cal' => 320, 'protein' => 18, 'fat' => 8, 'carbs' => 42],
            ['name' => 'Teriyaki Salmon', 'desc' => 'Grilled salmon glazed with sweet teriyaki sauce', 'price' => 1700, 'cal' => 550, 'protein' => 38, 'fat' => 22, 'carbs' => 48],
            ['name' => 'Spring Rolls', 'desc' => 'Fresh vegetables wrapped in rice paper with peanut sauce', 'price' => 850, 'cal' => 280, 'protein' => 8, 'fat' => 12, 'carbs' => 38],
            ['name' => 'Fried Rice', 'desc' => 'Wok-fried rice with eggs, vegetables, and choice of protein', 'price' => 1200, 'cal' => 720, 'protein' => 22, 'fat' => 24, 'carbs' => 98],
            ['name' => 'Ramen Bowl', 'desc' => 'Japanese noodle soup with pork, egg, and vegetables', 'price' => 1450, 'cal' => 780, 'protein' => 35, 'fat' => 28, 'carbs' => 88],
            ['name' => 'Mango Sticky Rice', 'desc' => 'Sweet glutinous rice with fresh mango and coconut cream', 'price' => 750, 'cal' => 420, 'protein' => 6, 'fat' => 14, 'carbs' => 72],
            ['name' => 'Vietnamese Pho', 'desc' => 'Traditional beef noodle soup with fresh herbs', 'price' => 1300, 'cal' => 650, 'protein' => 28, 'fat' => 18, 'carbs' => 85],
        ];

        $this->createMealsForRestaurant($manager, RestaurantFixtures::RESTAURANT_2, $asianMeals, 'asian_meal');

        // Healthy Bowl Meals
        $healthyMeals = [
            ['name' => 'Quinoa Power Bowl', 'desc' => 'Quinoa with grilled chicken, avocado, and mixed greens', 'price' => 1400, 'cal' => 520, 'protein' => 35, 'fat' => 18, 'carbs' => 52],
            ['name' => 'Greek Salad Bowl', 'desc' => 'Fresh vegetables, feta cheese, olives, and olive oil', 'price' => 1100, 'cal' => 380, 'protein' => 12, 'fat' => 28, 'carbs' => 24],
            ['name' => 'Grilled Salmon Bowl', 'desc' => 'Wild salmon with brown rice and steamed broccoli', 'price' => 1650, 'cal' => 580, 'protein' => 42, 'fat' => 24, 'carbs' => 45],
            ['name' => 'Keto Buddha Bowl', 'desc' => 'Low-carb bowl with cauliflower rice, chicken, and avocado', 'price' => 1500, 'cal' => 480, 'protein' => 38, 'fat' => 32, 'carbs' => 18],
            ['name' => 'Vegan Protein Bowl', 'desc' => 'Tofu, chickpeas, quinoa, and tahini dressing', 'price' => 1300, 'cal' => 550, 'protein' => 28, 'fat' => 22, 'carbs' => 58],
            ['name' => 'Acai Berry Bowl', 'desc' => 'Acai blend with granola, fruits, and honey', 'price' => 1050, 'cal' => 420, 'protein' => 12, 'fat' => 14, 'carbs' => 68],
            ['name' => 'Turkey Lettuce Wraps', 'desc' => 'Lean ground turkey with vegetables in lettuce cups', 'price' => 1200, 'cal' => 340, 'protein' => 32, 'fat' => 12, 'carbs' => 24],
            ['name' => 'Protein Smoothie Bowl', 'desc' => 'Protein-packed smoothie with nuts, seeds, and berries', 'price' => 950, 'cal' => 380, 'protein' => 24, 'fat' => 16, 'carbs' => 42],
            ['name' => 'Sweet Potato Bowl', 'desc' => 'Roasted sweet potato with black beans and avocado', 'price' => 1150, 'cal' => 490, 'protein' => 18, 'fat' => 16, 'carbs' => 72],
            ['name' => 'Chia Seed Pudding', 'desc' => 'Overnight chia pudding with almond milk and berries', 'price' => 850, 'cal' => 320, 'protein' => 10, 'fat' => 18, 'carbs' => 35],
        ];

        $this->createMealsForRestaurant($manager, RestaurantFixtures::RESTAURANT_3, $healthyMeals, 'healthy_meal');

        // BBQ Restaurant Meals
        $bbqMeals = [
            ['name' => 'Pulled Pork Sandwich', 'desc' => 'Slow-smoked pulled pork with BBQ sauce and coleslaw', 'price' => 1350, 'cal' => 780, 'protein' => 42, 'fat' => 35, 'carbs' => 68],
            ['name' => 'Beef Brisket Plate', 'desc' => 'Texas-style smoked brisket with cornbread', 'price' => 1800, 'cal' => 920, 'protein' => 52, 'fat' => 48, 'carbs' => 58],
            ['name' => 'Baby Back Ribs', 'desc' => 'Fall-off-the-bone ribs with tangy BBQ sauce', 'price' => 1900, 'cal' => 1050, 'protein' => 58, 'fat' => 62, 'carbs' => 42],
            ['name' => 'Smoked Chicken Wings', 'desc' => 'Crispy wings tossed in BBQ or buffalo sauce', 'price' => 1200, 'cal' => 720, 'protein' => 38, 'fat' => 42, 'carbs' => 32],
            ['name' => 'Loaded Mac & Cheese', 'desc' => 'Creamy mac and cheese with bacon and jalapeños', 'price' => 1100, 'cal' => 850, 'protein' => 28, 'fat' => 48, 'carbs' => 78],
            ['name' => 'Cornbread', 'desc' => 'Sweet and buttery Southern-style cornbread', 'price' => 600, 'cal' => 320, 'protein' => 6, 'fat' => 14, 'carbs' => 42],
            ['name' => 'Coleslaw Side', 'desc' => 'Creamy coleslaw with cabbage and carrots', 'price' => 550, 'cal' => 220, 'protein' => 2, 'fat' => 16, 'carbs' => 18],
            ['name' => 'Smoked Sausage', 'desc' => 'Juicy smoked sausage with peppers and onions', 'price' => 1250, 'cal' => 680, 'protein' => 32, 'fat' => 42, 'carbs' => 38],
            ['name' => 'Baked Beans', 'desc' => 'Slow-cooked beans with bacon and brown sugar', 'price' => 650, 'cal' => 380, 'protein' => 14, 'fat' => 8, 'carbs' => 68],
            ['name' => 'Pecan Pie Slice', 'desc' => 'Classic Southern pecan pie with vanilla ice cream', 'price' => 850, 'cal' => 520, 'protein' => 8, 'fat' => 28, 'carbs' => 62],
        ];

        $this->createMealsForRestaurant($manager, RestaurantFixtures::RESTAURANT_4, $bbqMeals, 'bbq_meal');

        // Fast Food Meals
        $fastFoodMeals = [
            ['name' => 'Classic Burger', 'desc' => 'Beef patty with lettuce, tomato, and special sauce', 'price' => 950, 'cal' => 680, 'protein' => 32, 'fat' => 38, 'carbs' => 52],
            ['name' => 'Chicken Nuggets', 'desc' => 'Crispy breaded chicken nuggets with dipping sauce', 'price' => 850, 'cal' => 520, 'protein' => 24, 'fat' => 28, 'carbs' => 42],
            ['name' => 'French Fries', 'desc' => 'Golden crispy fries with sea salt', 'price' => 450, 'cal' => 420, 'protein' => 6, 'fat' => 22, 'carbs' => 52],
            ['name' => 'Caesar Wrap', 'desc' => 'Grilled chicken wrap with Caesar dressing', 'price' => 1050, 'cal' => 580, 'protein' => 28, 'fat' => 24, 'carbs' => 58],
            ['name' => 'Fish & Chips', 'desc' => 'Battered fish with crispy fries and tartar sauce', 'price' => 1200, 'cal' => 820, 'protein' => 35, 'fat' => 42, 'carbs' => 78],
            ['name' => 'Veggie Burger', 'desc' => 'Plant-based patty with avocado and sprouts', 'price' => 1100, 'cal' => 520, 'protein' => 22, 'fat' => 24, 'carbs' => 58],
            ['name' => 'Onion Rings', 'desc' => 'Crispy beer-battered onion rings', 'price' => 650, 'cal' => 480, 'protein' => 8, 'fat' => 28, 'carbs' => 52],
            ['name' => 'Chicken Sandwich', 'desc' => 'Crispy or grilled chicken with pickles and mayo', 'price' => 1100, 'cal' => 650, 'protein' => 32, 'fat' => 32, 'carbs' => 58],
            ['name' => 'Milkshake', 'desc' => 'Thick and creamy milkshake in vanilla, chocolate, or strawberry', 'price' => 550, 'cal' => 520, 'protein' => 12, 'fat' => 22, 'carbs' => 68],
            ['name' => 'Garden Salad', 'desc' => 'Fresh mixed greens with choice of dressing', 'price' => 750, 'cal' => 220, 'protein' => 6, 'fat' => 12, 'carbs' => 24],
        ];

        $this->createMealsForRestaurant($manager, RestaurantFixtures::RESTAURANT_5, $fastFoodMeals, 'fastfood_meal');

        $manager->flush();
    }

    private function createMealsForRestaurant(
        ObjectManager $manager,
        string $restaurantRef,
        array $mealsData,
        string $referencePrefix
    ): void {
        /** @var Restaurant $restaurant */
        $restaurant = $this->getReference($restaurantRef, Restaurant::class);

        foreach ($mealsData as $index => $data) {
            $meal = new Meal();
            $meal->setName($data['name']);
            $meal->setDescription($data['desc']);
            $meal->setPrice($data['price']); // Price in cents
            $meal->setCalories($data['cal']);
            $meal->setProtein($data['protein']);
            $meal->setFat($data['fat']);
            $meal->setCarbs($data['carbs']);
            $meal->setRestaurant($restaurant);

            $manager->persist($meal);
            $this->addReference("{$referencePrefix}_$index", $meal);
        }
    }

    public function getDependencies(): array
    {
        return [
            RestaurantFixtures::class,
        ];
    }
}
