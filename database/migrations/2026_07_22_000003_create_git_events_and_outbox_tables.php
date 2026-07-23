<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('git_events', function (Blueprint $table): void {
            $table->id();
            $table->uuid('event_uuid')->unique();
            $table->foreignId('repository_id')->constrained()->cascadeOnDelete();
            $table->string('ref', 1024);
            $table->string('old_sha', 64);
            $table->string('new_sha', 64);
            $table->timestamp('received_at');
            $table->timestamps();
        });

        Schema::create('outbox_events', function (Blueprint $table): void {
            $table->id();
            $table->uuid('event_uuid')->unique();
            $table->string('event_type', 120);
            $table->unsignedSmallInteger('version')->default(1);
            $table->json('payload');
            $table->timestamp('available_at')->index();
            $table->timestamp('processed_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbox_events');
        Schema::dropIfExists('git_events');
    }
};
