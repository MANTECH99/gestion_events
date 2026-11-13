<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // « Désactiver » plutôt que supprimer : le lien cesse de fonctionner,
        // mais la fiche, l'historique des ventes et l'attribution de l'artiste
        // (artist_pos_id sur les réservations) sont conservés.
        Schema::table('artist_points_of_sale', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('token');
        });
    }

    public function down(): void
    {
        Schema::table('artist_points_of_sale', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
