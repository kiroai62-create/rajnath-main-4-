<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Owner-edited product page content (Admin › Gem store): highlights, sections and FAQs as JSON.
     * Null means the built-in ProductGuide text is shown.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->json('details')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('details');
        });
    }
};
