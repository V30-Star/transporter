<?php

namespace Database\Factories;

use App\Models\Sysuser;  // Use the Sysuser model
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sysuser>
 */
class SysuserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'fsysuserid' => $this->faker->unique()->userName(),  // Generate a unique fsysuserid (username)
            'fname' => $this->faker->name(),  // Generate a random full name
            'password' => static::$password ??= Hash::make('password'),  // Default password
            'fsalesman' => $this->faker->randomElement(['0', '1']),  // Salesman (0 or 1)
            'fuserlevel' => $this->faker->randomElement(['1', '2']),  // User level (1 for regular user, 2 for admin)
            'fcabang' => $this->faker->word(),  // Example value for the branch code (use real branch codes if needed)
            'fuserid' => 'System',  // This can be set to a default value
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the model should be a salesman.
     */
    public function salesman(): static
    {
        return $this->state(fn (array $attributes) => [
            'fsalesman' => '1',
        ]);
    }

    /**
     * Indicate that the model should be an admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'fuserlevel' => '2',  // Set to admin
        ]);
    }
}
