<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bus_ticket_cancellations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('support_ticket_id')->nullable()->constrained('support_tickets')->nullOnDelete();
            $table->string('ticket_number', 100);
            $table->timestamp('requested_at');
            $table->timestamp('executed_at')->nullable();
            $table->timestamp('original_ticket_at')->nullable();
            $table->unsignedBigInteger('amount_cents')->nullable();
            $table->foreignId('operator_id')->constrained('users')->restrictOnDelete();
            $table->string('result', 20);
            $table->text('reason');
            $table->string('authorization_reference', 150)->nullable();
            $table->text('evidence_notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'requested_at']);
            $table->index(['company_id', 'result']);
            $table->index(['company_id', 'ticket_number']);
            $table->index(['company_id', 'operator_id']);
            $table->index(['company_id', 'support_ticket_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bus_ticket_cancellations');
    }
};
