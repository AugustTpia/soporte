<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credential_references', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('service_account_id')->constrained('service_accounts')->cascadeOnDelete();
            $table->string('label', 120);
            $table->string('kind', 30)->default('password');
            $table->string('status', 20)->default('active');
            $table->timestamp('last_rotated_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['service_account_id', 'label'], 'credential_references_account_label_unique');
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credential_references');
    }
};
