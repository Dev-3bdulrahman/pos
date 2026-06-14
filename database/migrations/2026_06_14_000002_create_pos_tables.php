<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. POS Terminals
        Schema::create('pos_terminals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->string('name');
            $table->string('code')->index();
            $table->unsignedBigInteger('warehouse_id')->nullable()->index();
            $table->string('status')->default('active');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. POS Shifts
        Schema::create('pos_shifts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('user_id')->index(); // cashier
            $table->unsignedBigInteger('terminal_id')->index();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->decimal('opening_balance', 10, 2)->default(0.00);
            $table->decimal('expected_closing_balance', 10, 2)->default(0.00);
            $table->decimal('actual_closing_balance', 10, 2)->default(0.00);
            $table->decimal('difference', 10, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->string('status')->default('open'); // open, closed
            $table->timestamps();
            $table->softDeletes();
        });

        // 3. POS Sessions
        Schema::create('pos_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('shift_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('status')->default('active'); // active, paused, closed
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 4. POS Sales
        Schema::create('pos_sales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('shift_id')->index();
            $table->unsignedBigInteger('session_id')->nullable()->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->string('code')->index();
            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount', 10, 2)->default(0.00);
            $table->decimal('tax', 10, 2)->default(0.00);
            $table->decimal('total', 10, 2);
            $table->string('status')->default('completed'); // completed, returned, draft
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 5. POS Sale Items
        Schema::create('pos_sale_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pos_sale_id')->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('discount', 10, 2)->default(0.00);
            $table->decimal('tax', 10, 2)->default(0.00);
            $table->decimal('subtotal', 10, 2);
            $table->decimal('total', 10, 2);
            $table->timestamps();
        });

        // 6. POS Payments
        Schema::create('pos_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pos_sale_id')->index();
            $table->string('payment_method')->index(); // cash, card, split
            $table->decimal('amount', 10, 2);
            $table->string('reference_number')->nullable()->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        // 7. POS Cash Movements
        Schema::create('pos_cash_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shift_id')->index();
            $table->string('type'); // cash_in, cash_out
            $table->decimal('amount', 10, 2);
            $table->string('reason')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_cash_movements');
        Schema::dropIfExists('pos_payments');
        Schema::dropIfExists('pos_sale_items');
        Schema::dropIfExists('pos_sales');
        Schema::dropIfExists('pos_sessions');
        Schema::dropIfExists('pos_shifts');
        Schema::dropIfExists('pos_terminals');
    }
};
