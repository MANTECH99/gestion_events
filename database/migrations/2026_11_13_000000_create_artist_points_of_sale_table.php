<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('artist_points_of_sale', function (Blueprint $table) {
            $table->id();
            $table->foreignId('artist_id')->constrained('artists')->cascadeOnDelete();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('token', 40)->unique();
            $table->unsignedInteger('ticket_limit')->default(0);
            $table->timestamps();
        });

        // Traçage : une réservation en ligne créée via le lien d'un point de
        // vente artiste garde la trace de l'artiste crédité (canal « En ligne »
        // + nom de l'artiste dans la liste des réservations).
        Schema::table('reservations', function (Blueprint $table) {
            $table->foreignId('artist_pos_id')->nullable()->after('sold_by')
                ->constrained('artist_points_of_sale')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('artist_pos_id');
        });

        Schema::dropIfExists('artist_points_of_sale');
    }
};
