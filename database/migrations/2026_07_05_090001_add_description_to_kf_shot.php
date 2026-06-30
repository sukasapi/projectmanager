<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kf_shot', function (Blueprint $table) {
            $table->text('description')->nullable()->after('shot_code');
        });
    }

    public function down(): void
    {
        Schema::table('kf_shot', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
