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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('numTicket');
            $table->unsignedBigInteger('numTicketNoct')->nullable();

            $table->unsignedBigInteger('user_create_ticket');
            $table->unsignedBigInteger('assigned_user_id'); // siempre asignado

            $table->string('titleTicket', 100);
            $table->text('descriptionTicket'); // límite real: validación max:2000

            // ✅ status como integer para mapeo
            $table->unsignedTinyInteger('status')->default(1);

            $table->unsignedBigInteger('id_guardia')->nullable();

            $table->timestamps();

            // Índices útiles
            $table->index('numTicket');
            $table->index('numTicketNoct');
            $table->index('user_create_ticket');
            $table->index('assigned_user_id');
            $table->index('id_guardia');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
