<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TimezoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_uses_brasilia_time(): void
    {
        $this->assertSame('America/Sao_Paulo', config('app.timezone'));
        $this->assertSame(-3 * 3600, now()->getOffset());
    }

    public function test_dates_saved_in_utc_are_moved_back_three_hours(): void
    {
        $user = User::factory()->create();
        DB::table('users')->where('id', $user->id)->update(['last_login_at' => '2026-10-19 19:53:00', 'email_verified_at' => null]);

        $migration = require database_path('migrations/2026_10_19_195521_convert_timestamps_to_sao_paulo_time.php');
        $migration->up();

        $this->assertSame('2026-10-19 16:53:00', $user->fresh()->last_login_at->format('Y-m-d H:i:s'));
        $this->assertNull($user->fresh()->email_verified_at);

        $migration->down();

        $this->assertSame('2026-10-19 19:53:00', $user->fresh()->last_login_at->format('Y-m-d H:i:s'));
    }
}
