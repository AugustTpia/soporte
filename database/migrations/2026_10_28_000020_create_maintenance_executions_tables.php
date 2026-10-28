<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('maintenance_plan_id')->nullable()->constrained('maintenance_plans')->nullOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained('assets')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('support_ticket_id')->nullable()->constrained('support_tickets')->nullOnDelete();
            $table->string('target_type', 20);
            $table->string('target_label', 200);
            $table->string('type', 20);
            $table->string('status', 20)->default('scheduled');
            $table->timestamp('scheduled_at');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('technician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('findings')->nullable();
            $table->text('actions')->nullable();
            $table->text('recommendations')->nullable();
            $table->text('parts_used')->nullable();
            $table->unsignedSmallInteger('time_spent_minutes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'status'], 'maintenance_exec_company_status_idx');
            $table->index(['company_id', 'scheduled_at'], 'maintenance_exec_company_sched_idx');
            $table->index(['company_id', 'technician_id'], 'maintenance_exec_company_tech_idx');
            $table->index(['company_id', 'maintenance_plan_id'], 'maintenance_exec_company_plan_idx');
            $table->index(['company_id', 'support_ticket_id'], 'maintenance_exec_company_ticket_idx');
        });

        Schema::create('maintenance_execution_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_execution_id')->constrained('maintenance_executions', 'id', 'meci_exec_fk')->cascadeOnDelete();
            $table->foreignId('maintenance_plan_checklist_item_id')->nullable()->constrained('maintenance_plan_checklist_items', 'id', 'meci_plan_item_fk')->nullOnDelete();
            $table->string('label', 200);
            $table->boolean('is_required')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_completed')->default(false);
            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['maintenance_execution_id', 'sort_order'], 'meci_exec_order_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_execution_checklist_items');
        Schema::dropIfExists('maintenance_executions');
    }
};
