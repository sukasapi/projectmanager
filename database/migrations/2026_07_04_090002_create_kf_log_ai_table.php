<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Log pemanggilan AI (Gemini) — untuk audit & pemantauan oleh Super Admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kf_log_ai', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('kf_pengguna')->nullOnDelete();
            $table->string('model')->nullable();
            $table->string('status', 20)->default('ok'); // ok | error
            $table->text('prompt')->nullable();
            $table->text('response')->nullable();
            $table->string('error')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kf_log_ai');
    }
};
