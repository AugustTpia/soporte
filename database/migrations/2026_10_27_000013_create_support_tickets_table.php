<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('folio', 20)->nullable()->unique();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained('assets')->nullOnDelete();
            $table->foreignId('service_account_id')->nullable()->constrained('service_accounts')->nullOnDelete();
            $table->string('category', 40);
            $table->string('subject', 180);
            $table->text('description');
            $table->string('requester_name', 150)->nullable();
            $table->string('requester_contact', 150)->nullable();
            $table->string('priority', 20)->default('medium');
            $table->string('status', 20)->default('open');
            $table->timestamp('opened_at');
            $table->timestamp('due_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'priority']);
            $table->index(['company_id', 'category']);
            $table->index(['company_id', 'opened_at']);
            $table->index(['company_id', 'created_by']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
