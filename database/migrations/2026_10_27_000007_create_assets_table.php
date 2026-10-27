<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('internal_code', 40);
            $table->string('hostname', 255)->nullable();
            $table->string('category', 30);
            $table->string('brand', 120)->nullable();
            $table->string('model', 120)->nullable();
            $table->string('serial_number', 120)->nullable();
            $table->foreignId('location_id')->constrained('locations')->restrictOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('active');
            $table->string('operating_system', 120)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->date('purchase_date')->nullable();
            $table->date('warranty_until')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'internal_code']);
            $table->unique(['company_id', 'hostname']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'category']);
            $table->index(['company_id', 'serial_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
