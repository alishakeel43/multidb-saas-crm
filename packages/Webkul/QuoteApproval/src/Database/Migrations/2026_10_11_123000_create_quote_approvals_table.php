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
            $table->unsignedInteger('quote_id')->unique();
            $table->string('status')->default('draft'); // draft|pending|approved|rejected
            $table->unsignedInteger('requested_by')->nullable();
            $table->unsignedInteger('reviewed_by')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_approvals');
    }
};
