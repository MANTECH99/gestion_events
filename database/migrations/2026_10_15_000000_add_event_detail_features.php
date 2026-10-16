<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('artists', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('title')->nullable();
            $table->text('bio')->nullable();
            $table->string('image_path')->nullable();
            $table->timestamps();
        });

        Schema::create('event_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('image_path');
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('event_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('author_name');
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'created_at']);
        });

        Schema::table('events', function (Blueprint $table) {
            $table->unsignedSmallInteger('duration_minutes')->nullable()->after('event_date');
            $table->string('tags')->nullable()->after('event_type');
            $table->string('venue_image_path')->nullable()->after('image_path');
            $table->foreignId('artist_id')->nullable()->after('organizer_id')
                ->constrained('artists')->nullOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->text('bio')->nullable()->after('phone');
            $table->boolean('is_verified')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['bio', 'is_verified']);
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('artist_id');
            $table->dropColumn(['duration_minutes', 'tags', 'venue_image_path']);
        });

        Schema::dropIfExists('event_reviews');
        Schema::dropIfExists('event_images');
        Schema::dropIfExists('artists');
    }
};
