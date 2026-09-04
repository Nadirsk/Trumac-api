<?php

namespace Database\Seeders;

use App\Models\Version;
use Illuminate\Database\Seeder;

class VersionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $versions = [
            ['version' => '1.0.0'],
            ['version' => '1.1.0'],
            ['version' => '1.2.0'],
            ['version' => '2.0.0'],
            ['version' => '2.1.0'],
        ];

        foreach ($versions as $version) {
            Version::firstOrCreate($version);
        }
    }
}
