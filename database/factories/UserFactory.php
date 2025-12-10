<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
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
        // Generate realistic names with Lithuanian influence
        $lithuanianFirstNames = [
            'Andrius', 'Vytautas', 'Mindaugas', 'Darius', 'Tomas', 'Mantas', 'Rokas', 'Lukas',
            'Rūta', 'Gintarė', 'Austėja', 'Ieva', 'Gabija', 'Emilija', 'Kotryna', 'Ugnė',
        ];

        $lithuanianLastNames = [
            'Petrauskas', 'Kazlauskas', 'Jankauskas', 'Stankevičius', 'Vasiliauskas',
            'Petrauskienė', 'Kazlauskienė', 'Jankauskienė', 'Stankevičienė', 'Vasiliauskienė',
        ];

        // 60% chance of Lithuanian name, 40% international
        if ($this->faker->boolean(60)) {
            $firstName = $this->faker->randomElement($lithuanianFirstNames);
            $lastName = $this->faker->randomElement($lithuanianLastNames);
            $name = $firstName.' '.$lastName;
        } else {
            $name = $this->faker->name();
        }

        // Generate realistic email based on name
        $emailName = strtolower(str_replace([' ', 'ė', 'ą', 'į', 'ų', 'ū', 'č', 'š', 'ž'],
            ['', 'e', 'a', 'i', 'u', 'u', 'c', 's', 'z'], $name));
        $emailName = preg_replace('/[^a-z0-9]/', '', $emailName);

        // Lithuanian email domains
        $lithuanianDomains = ['gmail.com', 'yahoo.com', 'hotmail.com', 'inbox.lt', 'takas.lt', 'zebra.lt'];
        $domain = $this->faker->randomElement($lithuanianDomains);

        $email = $emailName.$this->faker->numberBetween(1, 999).'@'.$domain;

        // Most users are verified, some are not
        $emailVerifiedAt = $this->faker->boolean(85) ?
            $this->faker->dateTimeBetween('-2 years', 'now') : null;

        return [
            'name' => $name,
            'email' => $email,
            'email_verified_at' => $emailVerifiedAt,
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'phone' => $this->faker->optional(0.7)->phoneNumber(),
            'is_venue_owner' => $this->faker->boolean(15), // 15% chance of being venue owner
            'notification_preferences' => $this->faker->optional(0.5)->passthrough([
                'email_notifications' => $this->faker->boolean(90),
                'sms_notifications' => $this->faker->boolean(60),
                'push_notifications' => $this->faker->boolean(80),
                'reminder_hours' => $this->faker->randomElement([2, 4, 12, 24]),
            ]),
            'last_booking_at' => $this->faker->optional(0.6)->dateTimeBetween('-6 months', 'now'),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Create an admin user.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Admin User',
            'email' => 'admin@treatwell.com',
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Create a test user.
     */
    public function testUser(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Create a user with Lithuanian name.
     */
    public function lithuanian(): static
    {
        $lithuanianNames = [
            ['Andrius', 'Petrauskas'],
            ['Vytautas', 'Kazlauskas'],
            ['Mindaugas', 'Jankauskas'],
            ['Rūta', 'Petrauskienė'],
            ['Gintarė', 'Kazlauskienė'],
            ['Austėja', 'Jankauskienė'],
        ];

        $nameData = $this->faker->randomElement($lithuanianNames);
        $fullName = $nameData[0].' '.$nameData[1];

        return $this->state(fn (array $attributes) => [
            'name' => $fullName,
            'email' => strtolower($nameData[0]).'.'.strtolower($nameData[1]).'@inbox.lt',
        ]);
    }

    /**
     * Create a recently registered user.
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'created_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Create a long-time user.
     */
    public function veteran(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => $this->faker->dateTimeBetween('-3 years', '-1 year'),
            'created_at' => $this->faker->dateTimeBetween('-3 years', '-1 year'),
        ]);
    }

    /**
     * Create a user with a specific email domain.
     */
    public function withDomain(string $domain): static
    {
        return $this->state(function (array $attributes) use ($domain) {
            $username = strtolower(str_replace(' ', '.', $attributes['name']));
            $username = preg_replace('/[^a-z0-9.]/', '', $username);

            return [
                'email' => $username.'@'.$domain,
            ];
        });
    }

    /**
     * Create a user with admin privileges.
     */
    public function adminUser(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'System Administrator',
            'email' => 'admin@treatwell.com',
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Create a user for testing purposes.
     */
    public function forTesting(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'email_verified_at' => now(),
            'password' => 'password', // Will be hashed automatically
        ]);
    }

    /**
     * Create a user with a specific verification status.
     */
    public function verified(bool $verified = true): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => $verified ? now() : null,
        ]);
    }

    /**
     * Create a venue owner user.
     */
    public function venueOwner(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_venue_owner' => true,
            'phone' => $this->faker->phoneNumber(),
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Create a regular customer user.
     */
    public function customer(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_venue_owner' => false,
            'last_booking_at' => $this->faker->optional(0.8)->dateTimeBetween('-3 months', 'now'),
        ]);
    }
}
