<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credential_secrets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credential_reference_id')
                ->unique()
                ->constrained('credential_references')
                ->cascadeOnDelete();
            $table->longText('ciphertext');
            $table->string('key_id', 32);
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('rotated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credential_secrets');
    }
};
