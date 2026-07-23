<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('passkeys', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('authenticatable_id');
            $table->string('authenticatable_type');
            $table->string('credential_id')->unique();
            $table->text('credential_public_key');
            $table->unsignedBigInteger('counter')->default(0);
            $table->string('name')->nullable();
            $table->timestamps();

            $table->index(['authenticatable_id', 'authenticatable_type'], 'passkeys_authenticatable_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('passkeys');
    }
};
