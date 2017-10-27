<?php namespace Lovata\OmnipayShopaholic\Classes\Event;

use Cms\Classes\Page;
use Omnipay\Omnipay;
use Lovata\OrdersShopaholic\Models\Status;
use Lovata\OrdersShopaholic\Models\PaymentMethod;
use Lovata\OrdersShopaholic\Controllers\PaymentMethods;

/**
 * Class PaymentMethodModelHandler
 * @package Lovata\OmnipayShopaholic\Classes\Event
 * @author Andrey Kharanenka, a.khoronenko@lovata.com, LOVATA Group
 */
class PaymentMethodModelHandler
{
    /**
     * Add listeners
     * @param \Illuminate\Events\Dispatcher $obEvent
     */
    public function subscribe($obEvent)
    {
        PaymentMethod::extend(function ($obModel) {
            /** @var PaymentMethod $obModel*/
            $this->extendModel($obModel);
        });
    }

    /**
     * Extend PaymentMethod model
     * @param PaymentMethod $obModel
     */
    protected function extendModel($obModel)
    {
        $obModel->addFillable([
            'gateway_id',
            'gateway_currency',
            'payment_page',
            'gateway_property',
            'before_status_id',
            'after_status_id',
        ]);
        
        $obModel->addJsonable('gateway_property');
    }
}