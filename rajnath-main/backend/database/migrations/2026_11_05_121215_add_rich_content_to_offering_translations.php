<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Detailed service pages: step-by-step procedure, benefits and service-specific FAQs. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offering_translations', function (Blueprint $table) {
            $table->json('process')->nullable()->after('audience');   // list of steps
            $table->json('benefits')->nullable()->after('process');   // list of points
            $table->json('faqs')->nullable()->after('benefits');      // list of [question, answer]
        });
    }

    public function down(): void
    {
        Schema::table('offering_translations', function (Blueprint $table) {
            $table->dropColumn(['process', 'benefits', 'faqs']);
        });
    }
};
