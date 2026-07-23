<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repository_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('repository_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20)->default('read');
            $table->timestamps();

            $table->unique(['repository_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repository_members');
    }
};
