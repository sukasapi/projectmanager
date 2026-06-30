<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lifecycle episode: team lead + publish/close. Lihat WORKFLOW.md §2.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kf_proyek', function (Blueprint $table) {
            $table->foreignId('team_lead_id')->nullable()->after('client_id')->constrained('kf_pengguna')->nullOnDelete();
            $table->dateTime('published_at')->nullable()->after('team_lead_id');
            $table->dateTime('closed_at')->nullable()->after('published_at');
        });
    }

    public function down(): void
    {
        Schema::table('kf_proyek', function (Blueprint $table) {
            $table->dropConstrainedForeignId('team_lead_id');
            $table->dropColumn(['published_at', 'closed_at']);
        });
    }
};
