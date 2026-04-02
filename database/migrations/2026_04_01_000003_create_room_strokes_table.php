<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('room_strokes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->string('stroke_id', 64)->unique();
            $table->string('session_id');
            $table->string('display_name', 24);
            $table->string('tool', 16);
            $table->string('color', 16)->nullable();
            $table->unsignedTinyInteger('size');
            $table->json('points');
            $table->unsignedInteger('position');
            $table->timestamps();

            $table->index(['room_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_strokes');
    }
};
