<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modulos', function (Blueprint $table): void {
            $table->integer('idModulos')->comment('Id del Modulo');
            $table->primary('idModulos');
            $table->string('nmodulo', 60)->comment('Nombre del modulo');
            $table->tinyInteger('orden')->nullable()->comment('Orden de los modulos');
            $table->string('icono', 60)->nullable()->comment('Icono del modulo');
            $table->string('color', 60)->nullable()->comment('Color del modulo');
            $table->string('detalle', 60)->nullable()->comment('Texto descriptivo del modulo');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modulos');
    }
};
