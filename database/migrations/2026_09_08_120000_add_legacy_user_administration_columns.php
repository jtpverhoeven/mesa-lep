<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('enabled')->default(true)->after('password');
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->foreignId('groupLeader')->nullable()->after('guard_name')->constrained('users')->nullOnDelete();
            $table->boolean('superGroup')->default(false)->after('groupLeader');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('groupLeader');
            $table->dropColumn('superGroup');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('enabled');
        });
    }
};