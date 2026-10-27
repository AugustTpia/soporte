<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->string('name', 160);
            $table->string('legal_name', 200)->nullable();
            $table->string('tax_id', 40)->nullable()->unique();
            $table->string('email', 160)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('website', 190)->nullable();
            $table->string('industry', 120)->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->string('timezone', 64)->default('America/Mexico_City');
            $table->string('locale', 10)->default('es_MX');
            $table->string('logo_path', 255)->nullable();
            $table->json('settings')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
