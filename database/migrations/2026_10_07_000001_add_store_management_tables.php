<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStoreManagementTables extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable();
            $table->string('address', 500)->nullable();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('checkout_token', 64)->nullable()->unique();
            $table->boolean('stock_deducted')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('ghn_district_id')->nullable();
            $table->string('ghn_ward_code', 20)->nullable();
            $table->string('district_name', 150)->nullable();
            $table->string('ward_name', 150)->nullable();
            $table->string('ghn_order_code')->nullable()->index();
            $table->string('shipping_status')->default('pending');
            $table->index(['user_id', 'status']);
        });

        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('gateway', 30);
            $table->string('gateway_order_id')->nullable();
            $table->string('transaction_id')->nullable()->index();
            $table->decimal('amount', 15, 2);
            $table->string('status')->default('pending');
            $table->integer('result_code')->nullable();
            $table->text('message')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->unique(['gateway', 'gateway_order_id']);
            $table->index(['order_id', 'status']);
        });

        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status');
            $table->string('note', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('order_status_histories');
        Schema::dropIfExists('payment_transactions');
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['checkout_token']);
            $table->dropIndex(['ghn_order_code']);
            $table->dropIndex(['user_id', 'status']);
            $table->dropColumn([
                'checkout_token', 'stock_deducted', 'completed_at', 'ghn_district_id',
                'ghn_ward_code', 'district_name', 'ward_name', 'ghn_order_code', 'shipping_status',
            ]);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'address']);
        });
    }
}
