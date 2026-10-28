<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at');
            $table->foreignId('unassigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('unassigned_at')->nullable();
            $table->string('reason', 255)->nullable();
            $table->timestamps();

            $table->index(['ticket_id', 'unassigned_at']);
            $table->index(['user_id', 'unassigned_at']);
            $table->index(['ticket_id', 'user_id', 'unassigned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_assignments');
    }
};
