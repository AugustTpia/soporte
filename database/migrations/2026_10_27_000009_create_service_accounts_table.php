<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('service', 30);
            $table->string('username', 150);
            $table->string('instance', 255);
            $table->string('owner_name', 150)->nullable();
            $table->string('status', 20)->default('active');
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'service', 'instance', 'username'], 'service_accounts_identity_unique');
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'service']);
            $table->index('responsible_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_accounts');
    }
};
