<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('biographies', function (Blueprint $table) {
            $table->unsignedInteger('birth_year')->nullable()->after('race');
            $table->unsignedTinyInteger('birth_month')->nullable()->after('birth_year');
            $table->unsignedTinyInteger('birth_day')->nullable()->after('birth_month');
            $table->unsignedInteger('death_year')->nullable()->after('birth_day');
            $table->unsignedTinyInteger('death_month')->nullable()->after('death_year');
            $table->unsignedTinyInteger('death_day')->nullable()->after('death_month');
        });

        $rows = DB::table('biographies')->select('id', 'birth_date', 'death_date')->get();
        foreach ($rows as $row) {
            $updates = [];
            if (! empty($row->birth_date)) {
                $t = strtotime((string) $row->birth_date);
                if ($t !== false) {
                    $updates['birth_year'] = (int) date('Y', $t);
                    $updates['birth_month'] = (int) date('n', $t);
                    $updates['birth_day'] = (int) date('j', $t);
                }
            }
            if (! empty($row->death_date)) {
                $t = strtotime((string) $row->death_date);
                if ($t !== false) {
                    $updates['death_year'] = (int) date('Y', $t);
                    $updates['death_month'] = (int) date('n', $t);
                    $updates['death_day'] = (int) date('j', $t);
                }
            }
            if ($updates !== []) {
                DB::table('biographies')->where('id', $row->id)->update($updates);
            }
        }

        Schema::table('biographies', function (Blueprint $table) {
            $table->dropColumn(['birth_date', 'death_date']);
        });
    }

    public function down(): void
    {
        Schema::table('biographies', function (Blueprint $table) {
            $table->dropColumn([
                'birth_year',
                'birth_month',
                'birth_day',
                'death_year',
                'death_month',
                'death_day',
            ]);
            $table->date('birth_date')->nullable();
            $table->date('death_date')->nullable();
        });
    }
};
