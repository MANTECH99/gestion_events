<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organizer_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->dateTime('event_date')->index();
            $table->string('location');
            $table->unsignedInteger('total_tickets')->default(0);

            // concert | festival | sport | theatre | conference | evening | other
            $table->string('event_type')->default('concert')->index();

            $table->string('image_path')->nullable();

            // draft | published | cancelled | completed
            $table->string('status')->default('published')->index();

            $table->boolean('online_sales_enabled')->default(true);
            $table->timestamps();

            $table->index(['organizer_id', 'status']);
        });

        Schema::create('ticket_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            // XOF : devise sans décimales
            $table->unsignedInteger('price')->default(0);
            $table->unsignedInteger('quantity')->nullable();
            $table->unsignedInteger('sold_online')->default(0);
            $table->unsignedInteger('sold_physical')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 32)->unique();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_type_id')->constrained()->cascadeOnDelete();

            // Client connecté (nullable : réservation sans compte)
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone', 30)->nullable();

            $table->unsignedInteger('quantity');
            $table->unsignedInteger('unit_price');
            $table->unsignedInteger('total_amount');

            // pending | paid | failed | cancelled | refunded | expired
            $table->string('payment_status')->default('pending')->index();

            // online (DexPay) | physical (point de vente)
            $table->string('channel')->default('online')->index();

            // Point de vente ayant.encoder la vente physique
            $table->foreignId('sold_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('paid_at')->nullable();
            $table->string('payment_operator')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'payment_status']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->string('provider')->default('dexpay');
            $table->string('reference', 64)->unique();
            $table->string('session_id', 64)->nullable();
            $table->string('payment_url')->nullable();
            $table->string('status')->default('initiated')->index();
            $table->string('currency', 8)->default('XOF');
            $table->unsignedInteger('amount')->default(0);
            $table->string('operator', 40)->nullable();
            $table->string('transaction_id', 80)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });

        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_type_id')->constrained()->cascadeOnDelete();
            $table->string('holder_name');
            $table->string('holder_email')->nullable();
            $table->unsignedInteger('price')->default(0);

            // valid | used | refunded | cancelled
            $table->string('status')->default('valid')->index();

            $table->timestamp('used_at')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['event_id', 'status']);
        });

        Schema::create('validations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('agent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ticket_code', 32)->nullable()->index();

            // valid | invalid
            $table->string('status')->index();
            $table->string('message');
            $table->string('scanned_code')->nullable();
            $table->timestamps();
        });

        // Stock de tickets physiques attribué à un point de vente.
        Schema::create('stock_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('allocated')->default(0);
            $table->unsignedInteger('sold')->default(0);
            $table->timestamps();

            $table->unique(['ticket_type_id', 'user_id']);
        });

        // Ventes physiques déclarées par les points de vente.
        Schema::create('pos_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_allocation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('unit_price')->default(0);
            $table->unsignedInteger('total_amount')->default(0);
            $table->string('buyer_name');
            $table->string('buyer_phone', 30)->nullable();
            $table->string('buyer_email')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organizer_id')->constrained('users')->cascadeOnDelete();
            $table->string('reference', 64)->nullable()->index();
            $table->string('dexpay_id', 64)->nullable();
            $table->unsignedInteger('amount');
            $table->string('currency', 8)->default('XOF');
            $table->unsignedInteger('fees')->default(0);
            $table->string('destination_phone', 30);
            $table->string('recipient_name')->nullable();
            $table->string('operator', 40);
            $table->string('country_iso', 4)->default('SN');
            // PENDING | PROCESSING | COMPLETED | FAILED | CANCELLED
            $table->string('status')->default('PENDING')->index();
            $table->string('provider_name')->nullable();
            $table->string('provider_tx_id')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('source')->default('dexpay');
            $table->string('event')->nullable()->index();
            $table->string('reference', 64)->nullable()->index();
            $table->string('event_id_header', 80)->nullable();
            $table->boolean('signature_valid')->default(false);
            $table->boolean('processed')->default(false);
            $table->text('payload')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
        Schema::dropIfExists('payouts');
        Schema::dropIfExists('pos_sales');
        Schema::dropIfExists('stock_allocations');
        Schema::dropIfExists('validations');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('reservations');
        Schema::dropIfExists('ticket_types');
        Schema::dropIfExists('events');
    }
};
