<?php

namespace Entity;

use App\Entity\Meal;
use App\Entity\MealPlan;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

class MealPlanValidationTest extends TestCase
{
    private $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testMealPlanMustHaveAtLeastOneMeal()
    {
        $plan = new MealPlan();
        $plan->setName("Test Plan");
        // No meals added

        $errors = $this->validator->validate($plan);

        // Should have error about min count
        $this->assertGreaterThan(0, count($errors));
        $this->assertEquals('You must select at least one meal.', $errors[0]->getMessage());
    }

    public function testMealPlanCannotHaveMoreThanFiveMeals()
    {
        $plan = new MealPlan();
        $plan->setName("Big Plan");

        // Add 6 meals
        for ($i = 0; $i < 6; $i++) {
            $meal = new Meal();
            $meal->setName("Meal $i");
            $plan->addMeal($meal);
        }

        $errors = $this->validator->validate($plan);

        // Should have error about max count
        $this->assertGreaterThan(0, count($errors));
        $this->assertEquals('You cannot select more than 5 meals in a plan.', $errors[0]->getMessage());
    }

    public function testValidMealPlan()
    {
        $plan = new MealPlan();
        $plan->setName("Valid Plan");

        $meal = new Meal();
        $plan->addMeal($meal); // 1 meal

        $errors = $this->validator->validate($plan);

        // Note: There might be other validation errors (like null price) depending on validation groups.
        // We only care about the count check for this test context.
        // Let's filter errors for 'meals' property.

        $mealsErrors = [];
        foreach ($errors as $error) {
            if ($error->getPropertyPath() === 'meals') {
                $mealsErrors[] = $error;
            }
        }

        $this->assertCount(0, $mealsErrors, 'Should not have errors on meals property');
    }
}
