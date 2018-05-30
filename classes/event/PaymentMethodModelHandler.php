<?php namespace Lovata\OmnipayShopaholic\Classes\Event;

use Lovata\OrdersShopaholic\Models\PaymentMethod;

/**
 * Class PaymentMethodModelHandler
 * @package Lovata\OmnipayShopaholic\Classes\Event
 * @author  Andrey Kharanenka, a.khoronenko@lovata.com, LOVATA Group
 */
class PaymentMethodModelHandler
{
    /**
     * Add listeners
     */
    public function subscribe()
    {
        PaymentMethod::extend(function ($obModel) {
            /** @var PaymentMethod $obModel */
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
            'gateway_property',
            'before_status_id',
            'after_status_id',
        ]);

        $obModel->addJsonable('gateway_property');

        $obModel->addDynamicMethod('setBeforeStatusIdAttribute', function ($sValue) use ($obModel) {

            if (empty($sValue)) {
                $sValue = 0;
            }

            $obModel->attributes['before_status_id'] = $sValue;
        });

        $obModel->addDynamicMethod('setAfterStatusIdAttribute', function ($sValue) use ($obModel) {

            if (empty($sValue)) {
                $sValue = 0;
            }

            $obModel->attributes['after_status_id'] = $sValue;
        });
    }
}
