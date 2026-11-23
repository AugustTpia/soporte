<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name', 160);
            $table->text('description')->nullable();
            $table->string('status', 20)->default('planned');
            $table->foreignId('manager_id')->constrained('users')->restrictOnDelete();
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'status'], 'projects_company_status_idx');
            $table->index(['company_id', 'manager_id'], 'projects_company_manager_idx');
        });

        Schema::create('project_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained('projects', 'id', 'pm_project_fk')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['project_id', 'user_id'], 'pm_project_user_unique');
        });

        Schema::create('project_tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained('projects', 'id', 'pt_project_fk')->cascadeOnDelete();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('priority', 20)->default('medium');
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('due_date')->nullable();
            $table->foreignId('ticket_id')->nullable()->constrained('support_tickets', 'id', 'pt_ticket_fk')->nullOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['ticket_id'], 'pt_ticket_unique');
            $table->index(['project_id', 'status', 'sort_order'], 'pt_project_status_sort_idx');
            $table->index(['project_id', 'assigned_user_id'], 'pt_project_assignee_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_tasks');
        Schema::dropIfExists('project_members');
        Schema::dropIfExists('projects');
    }
};
