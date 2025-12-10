<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            ['name' => 'United Kingdom', 'code' => 'GB', 'slug' => 'united-kingdom'],
            ['name' => 'Germany', 'code' => 'DE', 'slug' => 'germany'],
            ['name' => 'France', 'code' => 'FR', 'slug' => 'france'],
        ];

        foreach ($countries as $countryData) {
            $country = Country::create($countryData);

            // Create cities for each country
            $cities = match ($country->code) {
                'GB' => [
                    ['name' => 'London', 'slug' => 'london', 'latitude' => 51.5074, 'longitude' => -0.1278],
                    ['name' => 'Manchester', 'slug' => 'manchester', 'latitude' => 53.4808, 'longitude' => -2.2426],
                ],
                'DE' => [
                    ['name' => 'Berlin', 'slug' => 'berlin', 'latitude' => 52.5200, 'longitude' => 13.4050],
                    ['name' => 'Munich', 'slug' => 'munich', 'latitude' => 48.1351, 'longitude' => 11.5820],
                ],
                'FR' => [
                    ['name' => 'Paris', 'slug' => 'paris', 'latitude' => 48.8566, 'longitude' => 2.3522],
                    ['name' => 'Lyon', 'slug' => 'lyon', 'latitude' => 45.7640, 'longitude' => 4.8357],
                ],
            };

            foreach ($cities as $cityData) {
                $city = $country->cities()->create($cityData);

                // Create sample venues for each city
                for ($i = 1; $i <= 3; $i++) {
                    $city->venues()->create([
                        'name' => "Sample Spa {$i} - {$city->name}",
                        'slug' => "sample-spa-{$i}-".strtolower($city->name),
                        'description' => "A beautiful spa in {$city->name} offering relaxing treatments.",
                        'address' => "123 Main Street, {$city->name}",
                        'latitude' => $city->latitude + (rand(-100, 100) / 10000),
                        'longitude' => $city->longitude + (rand(-100, 100) / 10000),
                        'phone' => '+44 20 1234 567'.$i,
                        'email' => "spa{$i}@{$city->slug}.com",
                        'website' => "https://spa{$i}-{$city->slug}.com",
                        'rating' => rand(35, 50) / 10,
                        'rating_count' => rand(10, 100),
                        'is_active' => true,
                    ]);
                }
            }
        }
    }
}
