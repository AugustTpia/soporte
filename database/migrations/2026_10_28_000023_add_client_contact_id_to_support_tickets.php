<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->foreignId('client_contact_id')
                ->nullable()
                ->after('service_account_id')
                ->constrained('client_contacts')
                ->nullOnDelete();

            $table->index(['company_id', 'client_contact_id'], 'support_tickets_company_contact_idx');
        });
    }

    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropIndex('support_tickets_company_contact_idx');
            $table->dropConstrainedForeignId('client_contact_id');
        });
    }
};
