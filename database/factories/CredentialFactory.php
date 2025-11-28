<?php

namespace Database\Factories;

use App\Enums\Integrations;
use App\Models\Credential;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Credential>
 */
class CredentialFactory extends Factory
{
    protected $model = Credential::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'integration' => Integrations::GitHub,
            'data' => [
                'token' => 'github_pat_'.fake()->bothify(str_repeat('#', 20)),
                'user' => [
                    'id' => fake()->randomNumber(),
                    'login' => fake()->userName(),
                    'name' => fake()->name(),
                    'email' => fake()->safeEmail(),
                    'avatar_url' => 'https://avatars.githubusercontent.com/u/'.fake()->randomNumber(),
                ],
                'validated_at' => now()->toIso8601String(),
            ],
        ];
    }
}
