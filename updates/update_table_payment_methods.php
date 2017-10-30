<?php namespace Lovata\OmnipayShopaholic\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * Class UpdateTablePaymentMethods
 * @package Lovata\OmnipayShopaholic\Updates
 */
class UpdateTablePaymentMethods extends Migration
{
    /**
     * Apply migration
     */
    public function up()
    {
        if(!Schema::hasTable('lovata_orders_shopaholic_payment_methods')) {
            return;
        }

        Schema::table('lovata_orders_shopaholic_payment_methods', function(Blueprint $obTable)
        {
            $obTable->string('gateway_id')->nullable();
            $obTable->string('gateway_currency')->nullable();
            $obTable->text('gateway_property')->nullable();
            $obTable->integer('before_status_id')->nullable()->default(0);
            $obTable->integer('after_status_id')->nullable()->default(0);
        });
    }

    /**
     * Rollback migration
     */
    public function down()
    {
        if(!Schema::hasColumn('lovata_orders_shopaholic_payment_methods', 'gateway_id'))
        {
            return;
        }

        Schema::table('lovata_orders_shopaholic_payment_methods', function(Blueprint $obTable) {

            $obTable->dropColumn([
                'gateway_id',
                'gateway_currency',
                'gateway_property',
                'before_status_id',
                'after_status_id',
            ]);
        });
    }
}