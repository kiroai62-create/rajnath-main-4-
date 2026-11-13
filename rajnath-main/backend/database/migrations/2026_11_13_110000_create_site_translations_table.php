<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Translation memory for the website: every English sentence a visitor sees, once per language.
 * Rows start "pending" and are filled by AI (queued job) or typed by staff in Admin › Languages.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_translations', function (Blueprint $table) {
            $table->id();
            $table->string('locale', 8);
            $table->char('hash', 40);                       // sha1 of the normalised English text
            $table->text('source');
            $table->text('text')->nullable();
            $table->string('status', 10)->default('pending'); // pending | done | failed
            $table->string('origin', 10)->nullable();         // ai | manual
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamps();
            $table->unique(['locale', 'hash']);
            $table->index(['locale', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_translations');
    }
};
