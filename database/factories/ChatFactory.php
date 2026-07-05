<?php

namespace Database\Factories;

use App\Models\Chat;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ChatFactory extends Factory
{
    protected $model = Chat::class;

    public function definition(): array
    {
        return [
            'type' => $this->faker->word(),
            'title' => $this->faker->word(),
            'avatar_url' => $this->faker->url(),
            'creator_id' => $this->faker->randomNumber(),
            'last_message_id' => $this->faker->word(),
            'last_message_text' => $this->faker->text(),
            'last_message_at' => Carbon::now(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
