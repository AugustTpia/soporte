<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('target_type', 20);
            $table->foreignId('asset_id')->nullable()->constrained('assets')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('target_label', 200);
            $table->string('title', 160);
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('interval_value');
            $table->string('interval_unit', 10);
            $table->timestamp('next_due_at');
            $table->timestamp('last_completed_at')->nullable();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'status'], 'maintenance_plans_company_status_idx');
            $table->index(['company_id', 'next_due_at'], 'maintenance_plans_company_due_idx');
            $table->index(['company_id', 'assigned_user_id'], 'maintenance_plans_company_assignee_idx');
            $table->index(['company_id', 'asset_id'], 'maintenance_plans_company_asset_idx');
        });

        Schema::create('maintenance_plan_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_plan_id')->constrained('maintenance_plans', 'id', 'mpci_plan_fk')->cascadeOnDelete();
            $table->string('label', 200);
            $table->boolean('is_required')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['maintenance_plan_id', 'sort_order'], 'mpci_plan_order_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_plan_checklist_items');
        Schema::dropIfExists('maintenance_plans');
    }
};
