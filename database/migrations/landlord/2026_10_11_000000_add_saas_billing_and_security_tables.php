<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('landlord')->table('plans', function (Blueprint $table) {
            $table->unsignedInteger('storage_quota_mb')->nullable()->after('sort_order');
            $table->string('stripe_price_id')->nullable()->after('storage_quota_mb');
        });

        Schema::connection('landlord')->table('tenants', function (Blueprint $table) {
            $table->unsignedInteger('storage_quota_mb')->nullable()->after('trial_ends_at');
            $table->string('stripe_customer_id')->nullable()->after('storage_quota_mb');
        });

        Schema::connection('landlord')->table('platform_users', function (Blueprint $table) {
            $table->text('two_factor_secret')->nullable()->after('remember_token');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
        });

        Schema::connection('landlord')->create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number', 64)->unique();
            $table->string('status', 32)->default('draft');
            $table->char('currency', 3)->default('USD');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->timestamp('due_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('stripe_invoice_id')->nullable();
            $table->string('stripe_checkout_session_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        Schema::connection('landlord')->create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_amount', 12, 2)->default(0);
            $table->decimal('amount', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::connection('landlord')->create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider', 32)->default('manual');
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3)->default('USD');
            $table->string('status', 32)->default('pending');
            $table->string('provider_payment_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index('provider_payment_id');
        });
    }

    public function down(): void
    {
        Schema::connection('landlord')->dropIfExists('payments');
        Schema::connection('landlord')->dropIfExists('invoice_items');
        Schema::connection('landlord')->dropIfExists('invoices');

        Schema::connection('landlord')->table('platform_users', function (Blueprint $table) {
            $table->dropColumn([
                'two_factor_secret',
                'two_factor_recovery_codes',
                'two_factor_confirmed_at',
            ]);
        });

        Schema::connection('landlord')->table('tenants', function (Blueprint $table) {
            $table->dropColumn(['storage_quota_mb', 'stripe_customer_id']);
        });

        Schema::connection('landlord')->table('plans', function (Blueprint $table) {
            $table->dropColumn(['storage_quota_mb', 'stripe_price_id']);
        });
    }
};
