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
            ['name' => 'Pizza Margherita', 'desc' => 'Klasyczna pizza z pomidorami, mozzarellą i świeżą bazylią', 'price' => 1250, 'cal' => 650, 'protein' => 25, 'fat' => 22, 'carbs' => 85],
            ['name' => 'Spaghetti Carbonara', 'desc' => 'Kremowy makaron z boczkiem, jajkami i parmezanem', 'price' => 1450, 'cal' => 720, 'protein' => 28, 'fat' => 32, 'carbs' => 75],
            ['name' => 'Lasagne Bolognese', 'desc' => 'Warstwowy makaron z sosem mięsnym i beszamelem', 'price' => 1550, 'cal' => 850, 'protein' => 35, 'fat' => 38, 'carbs' => 90],
            ['name' => 'Sałatka Caprese', 'desc' => 'Świeża mozzarella, pomidory i bazylia z oliwą z oliwek', 'price' => 950, 'cal' => 320, 'protein' => 18, 'fat' => 24, 'carbs' => 12],
            ['name' => 'Risotto ai Funghi', 'desc' => 'Kremowe risotto grzybowe z parmezanem', 'price' => 1350, 'cal' => 580, 'protein' => 15, 'fat' => 18, 'carbs' => 82],
            ['name' => 'Penne Arrabbiata', 'desc' => 'Pikantny makaron pomidorowy z czosnkiem i chilli', 'price' => 1150, 'cal' => 520, 'protein' => 16, 'fat' => 12, 'carbs' => 88],
            ['name' => 'Kurczak Parmigiana', 'desc' => 'Panierowany kurczak z sosem marinara i mozzarellą', 'price' => 1650, 'cal' => 780, 'protein' => 42, 'fat' => 35, 'carbs' => 65],
            ['name' => 'Bruschetta', 'desc' => 'Grzanki z pomidorami, czosnkiem i oliwą z oliwek', 'price' => 750, 'cal' => 280, 'protein' => 8, 'fat' => 14, 'carbs' => 32], // FIX: Changed 750 (float 7.50) to 750 (int)
            ['name' => 'Tiramisu', 'desc' => 'Klasyczny włoski deser z kawą i mascarpone', 'price' => 850, 'cal' => 450, 'protein' => 9, 'fat' => 24, 'carbs' => 52], // FIX: Changed 850 (float 8.50) to 850 (int)
            ['name' => 'Zupa Minestrone', 'desc' => 'Pożywna zupa warzywna z makaronem', 'price' => 1050, 'cal' => 350, 'protein' => 10, 'fat' => 8, 'carbs' => 58],
        ];

        $this->createMealsForRestaurant($manager, RestaurantFixtures::RESTAURANT_1, $italianMeals, 'italian_meal');

        // Asian Restaurant Meals
        $asianMeals = [
            ['name' => 'Pad Thai z Kurczakiem', 'desc' => 'Popularny tajski makaron ryżowy z kurczakiem, orzeszkami i kiełkami fasoli', 'price' => 1550, 'cal' => 680, 'protein' => 30, 'fat' => 25, 'carbs' => 80],
            ['name' => 'Zielone Curry z Warzywami', 'desc' => 'Aromatyczne tajskie zielone curry z mlekiem kokosowym i świeżymi warzywami', 'price' => 1350, 'cal' => 550, 'protein' => 15, 'fat' => 30, 'carbs' => 55], // FIX: Changed 1350 to 1350
            ['name' => 'Smażony Ryż z Krewetkami', 'desc' => 'Ryż smażony z krewetkami, jajkiem i warzywami', 'price' => 1450, 'cal' => 580, 'protein' => 24, 'fat' => 18, 'carbs' => 78],
            ['name' => 'Sajgonki', 'desc' => 'Chrupiące sajgonki warzywne serwowane z sosem słodko-kwaśnym', 'price' => 800, 'cal' => 350, 'protein' => 10, 'fat' => 15, 'carbs' => 45],
            ['name' => 'Kurczak Kung Pao', 'desc' => 'Pikantny kurczak stir-fry z orzeszkami ziemnymi i warzywami', 'price' => 1450, 'cal' => 620, 'protein' => 32, 'fat' => 26, 'carbs' => 58], // FIX: Changed 1450 to 1450
            ['name' => 'Talerz Sushi', 'desc' => 'Asortyment nigiri i maki z świeżą rybą', 'price' => 1850, 'cal' => 480, 'protein' => 28, 'fat' => 12, 'carbs' => 64], // FIX: Changed 1850 to 1850
            ['name' => 'Zupa Tom Yum', 'desc' => 'Pikantna i kwaśna tajska zupa z krewetkami i grzybami', 'price' => 1100, 'cal' => 250, 'protein' => 15, 'fat' => 8, 'carbs' => 30],
            ['name' => 'Wonton w Bulionie', 'desc' => 'Delikatne pierożki w aromatycznym bulionie', 'price' => 1050, 'cal' => 300, 'protein' => 18, 'fat' => 10, 'carbs' => 35], // FIX: Changed 1050 to 1050
            ['name' => 'Wołowina po Seczuańsku', 'desc' => 'Kawałki wołowiny w ostrym sosie z warzywami', 'price' => 1600, 'cal' => 700, 'protein' => 40, 'fat' => 30, 'carbs' => 55],
            ['name' => 'Mochi (Deser)', 'desc' => 'Tradycyjne japońskie ciastka ryżowe, różne smaki', 'price' => 600, 'cal' => 180, 'protein' => 2, 'fat' => 5, 'carbs' => 32],
        ];

        $this->createMealsForRestaurant($manager, RestaurantFixtures::RESTAURANT_2, $asianMeals, 'asian_meal');

        // Healthy Restaurant Meals
        $healthyMeals = [
            ['name' => 'Sałatka Grecka z Fetą', 'desc' => 'Świeża sałatka z pomidorami, ogórkami, oliwkami, czerwoną cebulą i serem feta', 'price' => 1100, 'cal' => 400, 'protein' => 15, 'fat' => 30, 'carbs' => 20],
            ['name' => 'Łosoś Pieczony z Szparagami', 'desc' => 'Łosoś pieczony z cytryną i świeżymi szparagami', 'price' => 1700, 'cal' => 550, 'protein' => 45, 'fat' => 35, 'carbs' => 10],
            ['name' => 'Owsianka z Owocami', 'desc' => 'Owsianka na mleku migdałowym z jagodami, bananem i miodem', 'price' => 800, 'cal' => 380, 'protein' => 10, 'fat' => 12, 'carbs' => 60],
            ['name' => 'Miseczka Keto Buddha Bowl', 'desc' => 'Miseczka niskowęglowodanowa z ryżem kalafiorowym, kurczakiem i awokado', 'price' => 1500, 'cal' => 480, 'protein' => 38, 'fat' => 32, 'carbs' => 18],
            ['name' => 'Wegańska Miska Proteinowa', 'desc' => 'Tofu, ciecierzyca, komosa ryżowa i sos tahini', 'price' => 1399, 'cal' => 550, 'protein' => 28, 'fat' => 22, 'carbs' => 58],
            ['name' => 'Miseczka Acai Berry Bowl', 'desc' => 'Blend Acai z granolą, nasionami chia i świeżymi owocami', 'price' => 950, 'cal' => 420, 'protein' => 8, 'fat' => 10, 'carbs' => 75], // FIX: Changed 950 to 950
            ['name' => 'Smoothie Zielona Moc', 'desc' => 'Smoothie ze szpinakiem, jarmużem, bananem i wodą kokosową', 'price' => 799, 'cal' => 250, 'protein' => 5, 'fat' => 2, 'carbs' => 50],
            ['name' => 'Wrap Pełnoziarnisty z Humusem', 'desc' => 'Wrap z humusem, pieczonymi warzywami i rukolą', 'price' => 1099, 'cal' => 450, 'protein' => 14, 'fat' => 18, 'carbs' => 55],
            ['name' => 'Zupa Krem z Brokułów', 'desc' => 'Lekka i kremowa zupa brokułowa bez śmietany', 'price' => 850, 'cal' => 200, 'protein' => 10, 'fat' => 8, 'carbs' => 25], // FIX: Changed 850 to 850
            ['name' => 'Burger z Indyka w Sałacie', 'desc' => 'Burger z mielonego indyka serwowany w liściach sałaty zamiast bułki', 'price' => 1499, 'cal' => 420, 'protein' => 40, 'fat' => 22, 'carbs' => 15],
        ];

        $this->createMealsForRestaurant($manager, RestaurantFixtures::RESTAURANT_3, $healthyMeals, 'healthy_meal');

        // BBQ & Grill Restaurant Meals
        $bbqMeals = [
            ['name' => 'Żeberka BBQ (Pełna Porcja)', 'desc' => 'Wolno pieczone żeberka wieprzowe glazurowane sosem BBQ', 'price' => 2299, 'cal' => 950, 'protein' => 60, 'fat' => 65, 'carbs' => 35],
            ['name' => 'Pulled Pork Sandwich', 'desc' => 'Szarpana wieprzowina z sosem BBQ i Coleslawem w bułce brioche', 'price' => 1699, 'cal' => 800, 'protein' => 45, 'fat' => 40, 'carbs' => 60],
            ['name' => 'Wędzona Piersi Kurczaka', 'desc' => 'Soczysta wędzona pierś kurczaka z ziołami', 'price' => 1399, 'cal' => 450, 'protein' => 50, 'fat' => 18, 'carbs' => 5],
            ['name' => 'Brisket (Wolno Pieczona Wołowina)', 'desc' => 'Kawałki wołowiny marynowane i wolno pieczone', 'price' => 2075, 'cal' => 850, 'protein' => 55, 'fat' => 60, 'carbs' => 10],
            ['name' => 'Mac and Cheese', 'desc' => 'Kremowy makaron z serem cheddar i parmezanem', 'price' => 1175, 'cal' => 750, 'protein' => 28, 'fat' => 45, 'carbs' => 65],
            ['name' => 'Frytki Sweet Potato', 'desc' => 'Słodkie frytki ziemniaczane z sosem czosnkowym', 'price' => 850, 'cal' => 450, 'protein' => 5, 'fat' => 20, 'carbs' => 60], // FIX: Changed 850 to 850
            ['name' => 'Chili Con Carne', 'desc' => 'Ostre chili z wołowiną, fasolą i przyprawami', 'price' => 1475, 'cal' => 680, 'protein' => 48, 'fat' => 32, 'carbs' => 48],
            ['name' => 'Siekana Sałatka BBQ', 'desc' => 'Sałatka z grillowanym kurczakiem, kukurydzą, fasolą i sosem ranch', 'price' => 1275, 'cal' => 520, 'protein' => 35, 'fat' => 30, 'carbs' => 30],
            ['name' => 'Kukurydza na Kolbie', 'desc' => 'Grillowana kukurydza z masłem czosnkowym i przyprawami', 'price' => 775, 'cal' => 280, 'protein' => 8, 'fat' => 12, 'carbs' => 38],
            ['name' => 'Pieczenie Ziemniaki z Kwaśną Śmietaną', 'desc' => 'Duży pieczony ziemniak z kwaśną śmietaną i szczypiorkiem', 'price' => 975, 'cal' => 350, 'protein' => 8, 'fat' => 15, 'carbs' => 45],
        ];

        $this->createMealsForRestaurant($manager, RestaurantFixtures::RESTAURANT_4, $bbqMeals, 'bbq_meal');

        // Fast Food Restaurant Meals
        $fastFoodMeals = [
            ['name' => 'Klasyczny Burger Wołowy', 'desc' => 'Wołowy kotlet, ser, sałata, pomidor i sos majonezowy w bułce sezamowej', 'price' => 1150, 'cal' => 650, 'protein' => 32, 'fat' => 32, 'carbs' => 55], // FIX: Changed 1150 to 1150
            ['name' => 'Podwójny Cheeseburger', 'desc' => 'Dwa wołowe kotlety, podwójny ser, pikle i sos specjalny', 'price' => 1575, 'cal' => 850, 'protein' => 45, 'fat' => 48, 'carbs' => 50],
            ['name' => 'Chrupiące Nuggetsy z Kurczaka (10 szt.)', 'desc' => 'Chrupiące kawałki piersi kurczaka z sosem BBQ', 'price' => 900, 'cal' => 480, 'protein' => 25, 'fat' => 25, 'carbs' => 38],
            ['name' => 'Frytki (Duże)', 'desc' => 'Duża porcja złocistych frytek', 'price' => 600, 'cal' => 400, 'protein' => 5, 'fat' => 18, 'carbs' => 55],
            ['name' => 'Hot Dog Klasyczny', 'desc' => 'Parówka wołowa, musztarda, ketchup i relish w bułce', 'price' => 800, 'cal' => 380, 'protein' => 15, 'fat' => 20, 'carbs' => 35],
            ['name' => 'Pikantny Wrap z Kurczakiem', 'desc' => 'Grillowany kurczak, sałata, pomidor, ser i pikantny sos w tortilli', 'price' => 1050, 'cal' => 580, 'protein' => 35, 'fat' => 25, 'carbs' => 50], // FIX: Changed 1050 to 1050
            ['name' => 'Krążki Cebulowe', 'desc' => 'Panierowane i smażone krążki cebulowe', 'price' => 700, 'cal' => 350, 'protein' => 4, 'fat' => 20, 'carbs' => 40],
            ['name' => 'Milkshake', 'desc' => 'Kremowy milkshake, smak do wyboru: wanilia, czekolada lub truskawka', 'price' => 550, 'cal' => 520, 'protein' => 12, 'fat' => 22, 'carbs' => 68], // FIX: Changed 550 to 550
            ['name' => 'Sałatka Ogrodowa', 'desc' => 'Świeża mieszanka sałat z dressingiem do wyboru', 'price' => 750, 'cal' => 220, 'protein' => 6, 'fat' => 12, 'carbs' => 24], // FIX: Changed 750 to 750
            ['name' => 'Kanapka z Rybą (Fish Sandwich)', 'desc' => 'Panierowana ryba z sosem tatarskim i sałatą w bułce', 'price' => 950, 'cal' => 500, 'protein' => 25, 'fat' => 28, 'carbs' => 40], // FIX: Changed 950 to 950
        ];

        $this->createMealsForRestaurant($manager, RestaurantFixtures::RESTAURANT_5, $fastFoodMeals, 'fastfood_meal');

        // Polish Restaurant Meals
        $polishMeals = [
            ['name' => 'Pierogi Ruskie', 'desc' => 'Domowe pierogi z farszem twarogowo-ziemniaczanym i okrasą', 'price' => 1200, 'cal' => 550, 'protein' => 18, 'fat' => 15, 'carbs' => 85],
            ['name' => 'Kotlet Schabowy', 'desc' => 'Tradycyjny kotlet schabowy z ziemniakami i kapustą zasmażaną', 'price' => 1650, 'cal' => 850, 'protein' => 35, 'fat' => 45, 'carbs' => 60],
            ['name' => 'Żurek', 'desc' => 'Kwaśna zupa na zakwasie z białą kiełbasą i jajkiem', 'price' => 1100, 'cal' => 450, 'protein' => 20, 'fat' => 25, 'carbs' => 35],
            ['name' => 'Bigos', 'desc' => 'Gulasz z kiszonej kapusty z mięsem i grzybami', 'price' => 1400, 'cal' => 600, 'protein' => 30, 'fat' => 40, 'carbs' => 25],
            ['name' => 'Gołąbki', 'desc' => 'Liście kapusty nadziewane mięsem i ryżem w sosie pomidorowym', 'price' => 1350, 'cal' => 500, 'protein' => 22, 'fat' => 18, 'carbs' => 55],
            ['name' => 'Placki Ziemniaczane', 'desc' => 'Chrupiące placki ziemniaczane ze śmietaną', 'price' => 1050, 'cal' => 700, 'protein' => 12, 'fat' => 35, 'carbs' => 80],
            ['name' => 'Barszcz Czerwony', 'desc' => 'Czysty barszcz czerwony z uszkami', 'price' => 950, 'cal' => 250, 'protein' => 8, 'fat' => 5, 'carbs' => 40],
            ['name' => 'Sernik Krakowski', 'desc' => 'Tradycyjny sernik z rodzynkami i skórką pomarańczową', 'price' => 900, 'cal' => 400, 'protein' => 12, 'fat' => 22, 'carbs' => 45],
            ['name' => 'Kiełbasa z Grilla', 'desc' => 'Smażona kiełbasa z cebulą i chlebem', 'price' => 1150, 'cal' => 650, 'protein' => 25, 'fat' => 55, 'carbs' => 15],
            ['name' => 'Zrazy Wołowe', 'desc' => 'Zawijane zrazy wołowe z boczkiem i ogórkiem kiszonym w sosie własnym', 'price' => 1800, 'cal' => 600, 'protein' => 40, 'fat' => 30, 'carbs' => 10],
        ];

        $this->createMealsForRestaurant($manager, RestaurantFixtures::RESTAURANT_6, $polishMeals, 'polish_meal');

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
            $meal->setPrice($data['price']);
            $meal->setCalories($data['cal']);
            $meal->setProtein($data['protein']);
            $meal->setFat($data['fat']);
            $meal->setCarbs($data['carbs']);
            $meal->setRestaurant($restaurant);
            $meal->setImagePath('meal.jpg');

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
