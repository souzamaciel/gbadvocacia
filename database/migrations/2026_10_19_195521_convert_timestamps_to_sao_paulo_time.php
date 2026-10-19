<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The application clock moved from UTC to Brasília time (America/Sao_Paulo, UTC−3,
 * no daylight saving since 2019). Dates already saved were written in UTC, so they
 * are moved back 3 hours to keep showing the right time.
 */
return new class extends Migration
{
    /**
     * @var array<string, list<string>>
     */
    private array $columns = [
        'users' => ['email_verified_at', 'last_login_at', 'created_at', 'updated_at'],
        'password_reset_tokens' => ['created_at'],
        'failed_jobs' => ['failed_at'],
        'groups' => ['created_at', 'updated_at'],
        'topics' => ['created_at', 'updated_at'],
        'petition_logs' => ['created_at', 'updated_at'],
        'petitions' => ['generated_at', 'created_at', 'updated_at'],
        'petition_items' => ['reviewed_at', 'created_at', 'updated_at'],
        'roles' => ['created_at', 'updated_at'],
        'appointment_form_steps' => ['created_at', 'updated_at'],
        'appointment_form_questions' => ['created_at', 'updated_at'],
        'appointments' => ['generated_at', 'created_at', 'updated_at'],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->shift(-3);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->shift(3);
    }

    private function shift(int $hours): void
    {
        $sqlite = DB::connection()->getDriverName() === 'sqlite';

        foreach ($this->columns as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }

                $expression = $sqlite
                    ? "datetime({$column}, '{$hours} hours')"
                    : "DATE_ADD({$column}, INTERVAL {$hours} HOUR)";

                DB::table($table)->whereNotNull($column)->update([$column => DB::raw($expression)]);
            }
        }
    }
};
