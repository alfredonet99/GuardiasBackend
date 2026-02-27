<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('monit_redes', function (Blueprint $table) {
            $table->id();
            $table->integer('sucursal_id');
            $table->date('dateRed');
            $table->integer('statusRed');
            $table->dateTime('time_down');
            $table->dateTime('time_up')->nullable();
            $table->string('affectation',150);
            $table->text('reason')->nullable();
            $table->string('note',200)->nullable();
            $table->integer('statusMonit');
            $table->integer('user_create');
            $table->integer('user_update')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monit_redes');
    }
};
