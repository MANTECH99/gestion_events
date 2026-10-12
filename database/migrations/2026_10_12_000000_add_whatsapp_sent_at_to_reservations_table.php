<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Trace l'envoi automatique du billet par WhatsApp pour qu'une réservation
     * ne reçoive qu'un seul message, quel que soit le nombre de pages billets
     * ouvertes par le client.
     */
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->timestamp('whatsapp_sent_at')->nullable()->after('paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('whatsapp_sent_at');
        });
    }
};
