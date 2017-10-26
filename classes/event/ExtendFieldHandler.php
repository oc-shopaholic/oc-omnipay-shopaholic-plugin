<?php namespace Lovata\OmnipayShopaholic\Classes\Event;

use Lovata\OrdersShopaholic\Models\PaymentMethod;
use Lovata\OrdersShopaholic\Controllers\PaymentMethods;

/**
 * Class ExtendFieldHandler
 * @package Lovata\OmnipayShopaholic\Classes\Event
 * @author Andrey Kharanenka, a.khoronenko@lovata.com, LOVATA Group
 */
class ExtendFieldHandler
{
    /**
     * Add listeners
     * @param \Illuminate\Events\Dispatcher $obEvent
     */
    public function subscribe($obEvent)
    {
        $obEvent->listen('backend.form.extendFields', function($obWidget) {
            $this->extendPaymentMethodFields($obWidget);
        });
    }

    /**
     * Extend settings fields
     * @param \Backend\Widgets\Form $obWidget
     */
    protected function extendPaymentMethodFields($obWidget)
    {
        // Only for the Settings controller
        if (!$obWidget->getController() instanceof PaymentMethods) {
            return;
        }

        // Only for the Settings model
        if (!$obWidget->model instanceof PaymentMethod) {
            return;
        }

        // Add an extra birthday field
        $obWidget->addTabFields([
        ]);
    }
}