<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('login_otp_code')->nullable()->after('remember_token');
            $table->timestamp('login_otp_expires_at')->nullable()->after('login_otp_code');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['login_otp_code', 'login_otp_expires_at']);
        });
    }
};