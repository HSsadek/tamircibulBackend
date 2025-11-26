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
        Schema::table('customers', function (Blueprint $table) {
            $table->string('profile_image')->nullable()->after('user_id');
            $table->boolean('email_notifications')->default(true)->after('preferences');
            $table->boolean('sms_notifications')->default(true)->after('email_notifications');
            $table->boolean('push_notifications')->default(false)->after('sms_notifications');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['profile_image', 'email_notifications', 'sms_notifications', 'push_notifications']);
        });
    }
};
