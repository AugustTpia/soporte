<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->restrictOnDelete();
            $table->string('status', 20)->default('active')->after('email')->index();
            $table->string('job_title', 120)->nullable()->after('status');
            $table->string('phone', 30)->nullable()->after('job_title');
            $table->timestamp('last_login_at')->nullable()->after('phone');
            $table->timestamp('deactivated_at')->nullable()->after('last_login_at');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn([
                'company_id',
                'status',
                'job_title',
                'phone',
                'last_login_at',
                'deactivated_at',
                'deleted_at',
            ]);
        });
    }
};
