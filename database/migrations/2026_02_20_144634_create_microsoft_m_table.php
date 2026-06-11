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
        Schema::create('microsoft_m', function (Blueprint $table) {
            $table->id();
            $table->integer('serviceName');
            $table->date('revisionDate');
            $table->integer('state');
            $table->string('description');
            $table->string('ejecution');
            $table->integer('id_user');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('microsoft_m');
    }
};
