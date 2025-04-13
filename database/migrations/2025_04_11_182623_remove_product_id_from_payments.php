<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // database/migrations/[timestamp]_remove_product_id_from_payments.php
    public function up()
    {
        Schema::table('payments', function (Blueprint $table) {
            // First remove foreign key constraint
            $table->dropForeign(['product_id']);
            // Then remove the column
            $table->dropColumn('product_id');
        });
    }

    public function down()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->after('total_amount');
            $table->foreign('product_id')->references('id')->on('products');
        });
    }
};
