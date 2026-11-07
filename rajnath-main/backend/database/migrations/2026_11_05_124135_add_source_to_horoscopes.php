<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Rashifal written automatically by the AI writer has no staff author; "source" tells them apart. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('horoscopes', function (Blueprint $table) {
            $table->unsignedBigInteger('author_id')->nullable()->change();
            $table->string('source', 10)->default('staff')->after('status'); // staff | ai
        });
    }

    public function down(): void
    {
        Schema::table('horoscopes', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
