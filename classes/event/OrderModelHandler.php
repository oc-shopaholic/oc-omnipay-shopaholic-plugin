<?php namespace Lovata\OmnipayShopaholic\Classes\Event;

use Lovata\OrdersShopaholic\Models\Order;

/**
 * Class OrderModelHandler
 * @package Lovata\OmnipayShopaholic\Classes\Event
 * @author Andrey Kharanenka, a.khoronenko@lovata.com, LOVATA Group
 */
class OrderModelHandler
{
    /**
     * Add listeners
     * @param \Illuminate\Events\Dispatcher $obEvent
     */
    public function subscribe($obEvent)
    {
        Order::extend(function ($obModel) {
            /** @var Order $obModel*/
            $this->extendModel($obModel);
        });
    }

    /**
     * Extend Order model
     * @param Order $obModel
     */
    protected function extendModel($obModel)
    {
        $obModel->addFillable([
            'payment_data',
            'payment_response',
        ]);
        
        $obModel->addJsonable(['payment_data', 'payment_response']);
    }
}