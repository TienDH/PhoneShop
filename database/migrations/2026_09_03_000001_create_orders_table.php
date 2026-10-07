<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable(); // khách không đăng nhập vẫn đặt được
            $table->string('order_code')->unique();            // mã đơn hàng
            $table->string('receiver_name');
            $table->string('receiver_phone', 20);
            $table->text('receiver_address');
            $table->string('city')->nullable();
            $table->text('note')->nullable();
            $table->string('payment_method');                 // cod | momo | bank
            $table->string('payment_status')->default('pending'); // pending | paid
            $table->string('status')->default('pending');     // pending | confirmed | shipping | done | cancelled
            $table->decimal('subtotal', 15, 0)->default(0);
            $table->decimal('shipping_fee', 15, 0)->default(0);
            $table->decimal('total', 15, 0)->default(0);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::dropIfExists('orders');
    }
}
