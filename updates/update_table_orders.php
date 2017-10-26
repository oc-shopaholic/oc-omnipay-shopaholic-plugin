<?php namespace Lovata\OmnipayShopaholic\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * Class UpdateTableOrders
 * @package Lovata\OmnipayShopaholic\Updates
 */
class UpdateTableOrders extends Migration
{
    /**
     * Apply migration
     */
    public function up()
    {
        if(!Schema::hasTable('lovata_orders_shopaholic_orders')) {
            return;
        }

        Schema::table('lovata_orders_shopaholic_orders', function(Blueprint $obTable)
        {
            $obTable->text('payment_data')->nullable();
            $obTable->text('payment_response')->nullable();
        });
    }

    /**
     * Rollback migration
     */
    public function down()
    {
        if(!Schema::hasColumn('lovata_orders_shopaholic_orders', 'payment_data')) {
            return;
        }

        Schema::table('lovata_orders_shopaholic_orders', function(Blueprint $obTable) {
            $obTable->dropColumn(['payment_data', 'payment_response']);
        });
    }
}