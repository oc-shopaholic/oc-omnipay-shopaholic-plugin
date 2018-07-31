<?php namespace Lovata\OmnipayShopaholic\Classes\Event;

use Event;
use Lovata\OrdersShopaholic\Models\PaymentMethod;
use Lovata\OmnipayShopaholic\Classes\Helper\PaymentGateway;

/**
 * Class PaymentMethodModelHandler
 * @package Lovata\OmnipayShopaholic\Classes\Event
 * @author  Andrey Kharanenka, a.khoronenko@lovata.com, LOVATA Group
 */
class PaymentMethodModelHandler
{
    /**
     * Add listeners
     * @param \Illuminate\Events\Dispatcher $obEvent
     */
    public function subscribe($obEvent)
    {
        PaymentMethod::extend(function ($obElement) {
            /** @var PaymentMethod $obElement */

            //Get gateway list
            $arGatewayList = PaymentGateway::getOmnipayGatewayList();
            if (empty($arGatewayList)) {
                return;
            }

            foreach ($arGatewayList as $sGatewayCode) {

                $arEventData = Event::fire(PaymentGateway::EVENT_GET_PAYMENT_GATEWAY_CLASS, $sGatewayCode);
                if (!empty($arEventData)) {
                    foreach ($arEventData as $sPaymentGatewayClass) {
                        if (empty($sPaymentGatewayClass) || !class_exists($sPaymentGatewayClass)) {
                            continue;
                        }

                        $obElement->addGatewayClass($sGatewayCode, $sPaymentGatewayClass);
                        break;
                    }
                }

                $obElement->addGatewayClass($sGatewayCode, PaymentGateway::class);
            }
        });


        $obEvent->listen(PaymentMethod::EVENT_GET_GATEWAY_LIST, function () {
            $arGatewayList = PaymentGateway::getOmnipayGatewayList();
            if (empty($arGatewayList)) {
                return [];
            }

            $arResult = [];
            foreach ($arGatewayList as $sGatewayCode) {
                $arResult[$sGatewayCode] = $sGatewayCode;
            }

            return $arResult;
        });
    }
}
