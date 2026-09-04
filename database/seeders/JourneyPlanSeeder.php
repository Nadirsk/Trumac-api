<?php

namespace Database\Seeders;

use App\Models\JourneyPlan;
use App\Models\JourneyStop;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class JourneyPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates test journey plans with stops for IT Drivers and CG Drivers
     */
    public function run(): void
    {
        // Get IT Driver users (position_id = 4)
        $itDrivers = User::where('position_id', 4)->get();

        if ($itDrivers->isEmpty()) {
            echo "No IT Driver users found. Run UserSeeder first.\n";
            return;
        }

        // Get CG Driver users (position_id = 10)
        $cgDrivers = User::where('position_id', 10)->get();

        // Sample locations in Mumbai area for testing Google Maps
        $testLocations = [
            // Mumbai Head Office
            [
                'name' => 'Head Office',
                'latitude' => 19.0760,
                'longitude' => 72.8777,
                'address' => 'Sector 2 Shah Prima Kharghar, Mumbai',
            ],
            // Retail stop 1
            [
                'name' => 'Retail Store - Bandra',
                'latitude' => 19.0596,
                'longitude' => 72.8295,
                'address' => 'Bandra, Mumbai',
            ],
            // Retail stop 2
            [
                'name' => 'Retail Store - Andheri',
                'latitude' => 19.1136,
                'longitude' => 72.8697,
                'address' => 'Andheri, Mumbai',
            ],
            // Retail stop 3
            [
                'name' => 'Retail Store - Vile Parle',
                'latitude' => 19.1136,
                'longitude' => 72.8460,
                'address' => 'Vile Parle, Mumbai',
            ],
            // Warehouse
            [
                'name' => 'Central Warehouse',
                'latitude' => 19.1360,
                'longitude' => 72.9042,
                'address' => 'Mulund, Mumbai',
            ],
            // Franchise location
            [
                'name' => 'Franchise - Thane',
                'latitude' => 19.2183,
                'longitude' => 72.9781,
                'address' => 'Thane, Maharashtra',
            ],
            // Retail stop 4
            [
                'name' => 'Retail Store - Dadar',
                'latitude' => 18.9820,
                'longitude' => 72.8281,
                'address' => 'Dadar, Mumbai',
            ],
        ];

        // Create journey plans for IT Drivers
        $this->createJourneyPlans($itDrivers, $testLocations, 'IT');

        // Create journey plans for CG Drivers
        if ($cgDrivers->isNotEmpty()) {
            $this->createJourneyPlans($cgDrivers, $testLocations, 'CG');
        }

        echo "JourneyPlanSeeder: Created test journey plans with stops for IT and CG Drivers\n";
    }

    /**
     * Create journey plans for drivers
     */
    private function createJourneyPlans($drivers, $testLocations, $driverType = 'IT'): void
    {
        foreach ($drivers as $driver) {
            // Create journey plans for each driver
            for ($i = 1; $i <= 2; $i++) {
                // First journey for today, second for a past day
                $journeyDate = $i === 1 ? now()->toDateString() : now()->subDays(rand(1, 5))->toDateString();

                $journey = JourneyPlan::create([
                    'driver_id' => $driver->id,
                    'company_id' => 1,
                    'date' => $journeyDate,
                    'status' => $i === 1 ? 'planned' : 'completed',  // Today = planned, Past = completed
                    'start_time' => null,  // Will be set when journey starts
                    'end_time' => null,    // Will be set when journey ends
                    'start_odometer' => null,
                    'end_odometer' => null,
                    'start_latitude' => 19.0760,
                    'start_longitude' => 72.8777,
                    'end_latitude' => 19.2183,
                    'end_longitude' => 72.9781,
                    'notes' => "$driverType Driver test journey - " . $driver->first_name,
                ]);

                // Create 5-7 stops for each journey
                $stopCount = rand(5, 7);
                $locationsToUse = array_slice($testLocations, 0, $stopCount);

                foreach ($locationsToUse as $stopIndex => $location) {
                    $plannedTime = now()->setHour(8 + ($stopIndex * 1))->setMinute(15 * $stopIndex);

                    JourneyStop::create([
                        'journey_plan_id' => $journey->id,
                        'stop_type' => in_array($stopIndex, [0, 4]) ? 'warehouse' : 'delivery',
                        'sequence' => $stopIndex + 1,
                        'name' => $location['name'],
                        'address' => $location['address'],
                        'latitude' => $location['latitude'],
                        'longitude' => $location['longitude'],
                        'planned_time' => $plannedTime,
                        'arrival_time' => $journey->status === 'completed' ? $plannedTime->addMinutes(rand(5, 25)) : null,
                        'departure_time' => $journey->status === 'completed' ? $plannedTime->addMinutes(rand(30, 45)) : null,
                        'arrival_latitude' => $journey->status === 'completed' ? $location['latitude'] + (rand(-5, 5) / 1000) : null,
                        'arrival_longitude' => $journey->status === 'completed' ? $location['longitude'] + (rand(-5, 5) / 1000) : null,
                        'departure_latitude' => $journey->status === 'completed' ? $location['latitude'] + (rand(-5, 5) / 1000) : null,
                        'departure_longitude' => $journey->status === 'completed' ? $location['longitude'] + (rand(-5, 5) / 1000) : null,
                        'status' => $journey->status === 'completed' ? 'completed' : 'pending',
                        'notes' => "$driverType Driver - Stop at " . $location['name'],
                    ]);
                }
            }
        }
    }
}
