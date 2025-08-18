<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoadtaxSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table("roadtaxes")->insert([
            ["vehicle_number" => "VGY8704","expiry_date" => "2026-08-18", "created_at" => now(), "updated_at" => now()]
        ]);
    }
}
