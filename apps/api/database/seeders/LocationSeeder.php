<?php

namespace Database\Seeders;

use App\Domain\Employee\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            // minimum_wage reflects each state's actual state-level minimum
            // wage (illustrative — verify against current law before real use).
            ['name' => 'Dallas Distribution Center', 'code' => 'DAL-01', 'city' => 'Dallas', 'state' => 'TX', 'minimum_wage' => 7.25],
            ['name' => 'Columbus Retail Store #12', 'code' => 'CMH-12', 'city' => 'Columbus', 'state' => 'OH', 'minimum_wage' => 10.45],
            ['name' => 'Corporate HQ', 'code' => 'HQ-01', 'city' => 'Austin', 'state' => 'TX', 'minimum_wage' => 7.25],
        ] as $location) {
            Location::firstOrCreate(['code' => $location['code']], $location + ['country' => 'US', 'timezone' => 'America/Chicago']);
        }
    }
}
