<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('folder_token', 20)->nullable()->unique()->after('id');
        });

        foreach (DB::table('users')->get() as $user) {
            DB::table('users')->where('id', $user->id)->update([
                'folder_token' => Str::random(20),
            ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('folder_token', 20)->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('folder_token');
        });
    }
};
