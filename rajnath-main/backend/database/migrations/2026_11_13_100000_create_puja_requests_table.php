<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Puja on request": visitors describe the puja they need; the team calls them, agrees the
 * date, place and dakshina, and records it here. Replaces online puja and chadhava sales.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('puja_requests', function (Blueprint $table) {
            $table->id();
            $table->ulid('ref')->unique();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('name', 120);
            $table->string('phone_e164', 20);
            $table->string('email', 190)->nullable();
            $table->string('puja_type', 150);
            $table->text('purpose')->nullable();
            $table->string('mode', 20);                       // online | home | temple | not_sure
            $table->string('address', 300)->nullable();       // city / address for a home puja
            $table->date('preferred_date')->nullable();
            $table->date('alternate_date')->nullable();
            $table->string('preferred_time', 20)->nullable(); // morning | afternoon | evening | muhurat
            $table->string('participants', 500)->nullable();  // names for the sankalp
            $table->string('gotra', 100)->nullable();
            $table->date('birth_date')->nullable();
            $table->time('birth_time')->nullable();
            $table->string('birth_place', 200)->nullable();
            $table->string('language', 30)->nullable();
            $table->string('samagri', 20)->nullable();        // we_arrange | i_arrange | not_sure
            $table->text('message')->nullable();
            $table->string('status', 15)->default('new')->index(); // new | contacted | scheduled | completed | cancelled | spam
            $table->dateTime('scheduled_at')->nullable();     // UTC
            $table->string('priest', 120)->nullable();
            $table->unsignedBigInteger('quoted_minor')->nullable();
            $table->string('meeting_link', 300)->nullable();
            $table->text('staff_note')->nullable();
            $table->foreignId('handled_by')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('puja_requests');
    }
};
