<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('passkeys')) {
            Schema::drop('passkeys');
        }
    }

    public function down(): void
    {
        Schema::create('passkeys', function (Blueprint $table): void {
            $table->id();
            $table->morphs('authenticatable');
            $table->string('credential_id')->unique();
            $table->text('credential_public_key');
            $table->unsignedBigInteger('counter')->default(0);
            $table->string('name')->nullable();
            $table->timestamps();
        });
    }
};
