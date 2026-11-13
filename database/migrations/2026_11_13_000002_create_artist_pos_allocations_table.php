<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Quota du point de vente artiste PAR TYPE DE BILLET : l'assignation
        // réserve réellement le stock en ligne, comme le stock physique
        // attribué aux points de vente (StockAllocation).
        Schema::create('artist_pos_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('artist_pos_id')->constrained('artist_points_of_sale')->cascadeOnDelete();
            $table->foreignId('ticket_type_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('allocated')->default(0);
            $table->unsignedInteger('sold')->default(0);
            $table->timestamps();
            $table->unique(['artist_pos_id', 'ticket_type_id']);
        });

        // Reprise des quota existants (ticket_limit) : les ventes déjà
        // réalisées sont réparties sur leur type de billet, et le solde
        // restant est assigné au premier type de l'événement.
        $rows = DB::table('artist_points_of_sale')->get(['id', 'event_id', 'ticket_limit']);

        foreach ($rows as $pos) {
            if ((int) $pos->ticket_limit <= 0) {
                continue;
            }

            $typeIds = DB::table('ticket_types')
                ->where('event_id', $pos->event_id)
                ->orderBy('id')
                ->pluck('id');

            if ($typeIds->isEmpty()) {
                continue;
            }

            $soldByType = DB::table('reservations')
                ->where('artist_pos_id', $pos->id)
                ->where('payment_status', 'paid')
                ->groupBy('ticket_type_id')
                ->selectRaw('ticket_type_id, SUM(quantity) as total')
                ->pluck('total', 'ticket_type_id');

            $remaining = max(0, (int) $pos->ticket_limit - (int) $soldByType->sum());
            $first = true;

            foreach ($typeIds as $typeId) {
                $sold = (int) $soldByType->get($typeId, 0);
                $allocated = $sold + ($first ? $remaining : 0);
                $first = false;

                if ($allocated <= 0 && $sold <= 0) {
                    continue;
                }

                DB::table('artist_pos_allocations')->insert([
                    'artist_pos_id' => $pos->id,
                    'ticket_type_id' => $typeId,
                    'allocated' => $allocated,
                    'sold' => $sold,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        Schema::table('artist_points_of_sale', function (Blueprint $table) {
            $table->dropColumn('ticket_limit');
        });
    }

    public function down(): void
    {
        Schema::table('artist_points_of_sale', function (Blueprint $table) {
            $table->unsignedInteger('ticket_limit')->default(0);
        });

        Schema::dropIfExists('artist_pos_allocations');
    }
};
