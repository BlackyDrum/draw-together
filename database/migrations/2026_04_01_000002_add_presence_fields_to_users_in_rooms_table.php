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
        Schema::table('users_in_rooms', function (Blueprint $table) {
            $table->string('display_name', 24)->nullable()->after('room_id');
            $table->string('color', 16)->nullable()->after('display_name');
            $table->timestamp('last_seen_at')->nullable()->after('color');
            $table->index('last_seen_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users_in_rooms', function (Blueprint $table) {
            $table->dropIndex(['last_seen_at']);
            $table->dropColumn(['display_name', 'color', 'last_seen_at']);
        });
    }
};
