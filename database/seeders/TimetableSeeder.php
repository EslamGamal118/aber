<?php

namespace Database\Seeders;

use App\Models\Timetable;
use Illuminate\Database\Seeder;

/**
 * Weekly working hours for active providers (GET /api/vendor/timetables).
 */
class TimetableSeeder extends Seeder
{
    public const DAYS = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

    public function run(): void
    {
        foreach (ProviderSeeder::activeProviders() as $provider) {
            foreach (self::DAYS as $day) {
                // Coffee cart is closed on Fridays; burger truck opens late on weekends
                if ($provider->email === 'coffee@aber.test' && $day === 'friday') {
                    continue;
                }

                $weekend = in_array($day, ['friday', 'saturday'], true);

                Timetable::updateOrCreate(
                    ['provider_id' => $provider->id, 'day' => $day],
                    [
                        'start_time' => $weekend ? '16:00:00' : '11:00:00',
                        'end_time' => $weekend ? '02:00:00' : '23:30:00',
                    ]
                );
            }
        }
    }
}
