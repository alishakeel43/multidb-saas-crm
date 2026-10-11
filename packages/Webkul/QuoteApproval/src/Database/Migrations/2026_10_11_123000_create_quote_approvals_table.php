<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_approvals', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('quote_id');
            $table->string('status')->default('draft'); // draft|pending|approved|rejected
            $table->unsignedInteger('requested_by')->nullable();
            $table->unsignedInteger('reviewed_by')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // one approval row per quote
            $table->unique('quote_id', 'quote_approvals_quote_id_unique');

            // pending queue / status filters + recent listing
            $table->index(['status', 'updated_at'], 'quote_approvals_status_updated_idx');

            // reviewer / requester history
            $table->index('requested_by', 'quote_approvals_requested_by_idx');
            $table->index('reviewed_by', 'quote_approvals_reviewed_by_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_approvals');
    }
};
