<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_account_asset_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_account_id')->constrained('service_accounts')->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('assets')->restrictOnDelete();
            $table->timestamp('linked_at');
            $table->timestamp('unlinked_at')->nullable();
            $table->string('reason', 255)->nullable();
            $table->string('unlink_reason', 255)->nullable();
            $table->foreignId('linked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('unlinked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['service_account_id', 'unlinked_at']);
            $table->index(['asset_id', 'unlinked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_account_asset_links');
    }
};
