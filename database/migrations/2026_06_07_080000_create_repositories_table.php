<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repositories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('visibility', 20)->default('private');
            $table->uuid('storage_uuid')->unique();
            $table->string('state', 20)->default('provisioning');
            $table->string('default_branch')->default('main');
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['user_id', 'slug']);
            $table->index('visibility');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repositories');
    }
};
