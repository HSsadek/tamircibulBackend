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
        Schema::table('service_requests', function (Blueprint $table) {
            // Rating fields
            $table->integer('rating')->nullable()->after('status');
            $table->text('rating_comment')->nullable()->after('rating');
            $table->timestamp('rated_at')->nullable()->after('rating_comment');
            
            // Complaint fields
            $table->boolean('has_complaint')->default(false)->after('rated_at');
            $table->string('complaint_reason')->nullable()->after('has_complaint');
            $table->text('complaint_description')->nullable()->after('complaint_reason');
            $table->timestamp('complaint_date')->nullable()->after('complaint_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropColumn([
                'rating',
                'rating_comment',
                'rated_at',
                'has_complaint',
                'complaint_reason',
                'complaint_description',
                'complaint_date'
            ]);
        });
    }
};
