<?php

namespace Tests\Unit;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Запись в журнал активности
 */
class ActivityLogRecordTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_log_record_persists_on_default_connection(): void
    {
        $user = User::factory()->create();
        ActivityLog::record($user, null, 'account.test', 'Тест.', $user);

        $this->assertSame(1, DB::table('activity_logs')->count());
    }
}
