<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bus_ticket_cancellation_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bus_ticket_cancellation_id')
                ->constrained('bus_ticket_cancellations', 'id', 'bus_cancel_attachment_cancel_fk')
                ->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('original_name');
            $table->string('disk', 30)->default('local');
            $table->string('path', 500);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes');
            $table->timestamps();

            $table->index(
                ['company_id', 'bus_ticket_cancellation_id'],
                'bus_cancellation_attachment_company_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bus_ticket_cancellation_attachments');
    }
};
