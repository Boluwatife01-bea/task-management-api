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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'uuid')) {
                $table->uuid('uuid')->unique()->after('id');
            }
            
            if (!Schema::hasColumn('users', 'slug')) {
            $table->string('slug')->unique()->after('uuid');
        }
            $table->timestamp('last_login_at')->nullable()->after('email_verified_at');
            $table->string('avatar')->nullable()->after('last_login_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('uuid', 'slug', 'last_login_at', 'avatar');
        });
    }
};
