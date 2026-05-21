<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu', function (Blueprint $table): void {
            $table->integer('id')->autoIncrement();
            $table->integer('idModulos');
            $table->foreign('idModulos')->references('idModulos')->on('modulos');
            $table->string('nombre', 120)->comment('Nombre del menu');
            $table->string('url')->comment('url del menu');
            $table->string('icono', 60)->nullable()->comment('icono que tiene ese menu');
            $table->integer('id_menu')->nullable()->comment('Identificador del menu padre');
            $table->boolean('main')->default(true)->comment('Identificador de que el menu es padre de varios menus');
            $table->smallInteger('orden')->nullable()->comment('Orden del menu');
            $table->boolean('cesdo')->default(true)->comment('Estado del menu');
            $table->string('permission_name', 120)->nullable()->comment('Permiso spatie asociado al menu');
            $table->timestamps();

            $table->foreign('id_menu')->references('id')->on('menu')->nullOnDelete();
            $table->index(['idModulos', 'id_menu', 'cesdo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu');
    }
};
