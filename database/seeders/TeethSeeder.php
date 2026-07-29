<?php

namespace Database\Seeders;

use App\Models\Tooth;
use Illuminate\Database\Seeder;

class TeethSeeder extends Seeder
{
    public function run(): void
    {
        // FDI quadrants: 1 = upper right, 2 = upper left, 3 = lower left, 4 = lower right
        $quadrantLabels = [
            1 => 'Upper Right',
            2 => 'Upper Left',
            3 => 'Lower Left',
            4 => 'Lower Right',
        ];

        // Position 1 (nearest midline) -> Position 8 (wisdom tooth)
        $positionNames = [
            1 => 'Central Incisor',
            2 => 'Lateral Incisor',
            3 => 'Canine',
            4 => 'First Premolar',
            5 => 'Second Premolar',
            6 => 'First Molar',
            7 => 'Second Molar',
            8 => 'Third Molar',
        ];

        foreach ($quadrantLabels as $quadrant => $quadrantLabel) {
            foreach ($positionNames as $position => $positionName) {
                $fdiNumber = "{$quadrant}{$position}";

                Tooth::firstOrCreate(
                    ['fdi_number' => $fdiNumber],
                    [
                        'name' => "{$quadrantLabel} {$positionName}",
                        'quadrant' => $quadrant,
                        'position_in_quadrant' => $position,
                        'type' => 'permanent',
                        'is_active' => true,
                    ]
                );
            }
        }
        // 32 rows total. Primary (deciduous) teeth (FDI 51-85) can be added
        // later via a separate seeder run — no schema change needed, just
        // more rows with type = 'primary'.
    }
}
