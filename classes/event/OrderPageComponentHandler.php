<?php namespace Lovata\OmnipayShopaholic\Classes\Event;

use Omnipay\Omnipay;
use Omnipay\Common\CreditCard;
use Lovata\OrdersShopaholic\Components\OrderPage;

/**
 * Class OrderPageComponentHandler
 * @package Lovata\OmnipayShopaholic\Classes\Event
 * @author Andrey Kharanenka, a.khoronenko@lovata.com, LOVATA Group
 */
class OrderPageComponentHandler
{
    /**
     * Add listeners
     * @param \Illuminate\Events\Dispatcher $obEvent
     */
    public function subscribe($obEvent)
    {
        OrderPage::extend(function($obComponent) {
            /** @var OrderPage $obComponent */
            $this->addPaymentGatewayMethods($obComponent);
        });
    }

    /**
     * Add processing payment gateway methods
     * @param OrderPage $obComponent
     */
    protected function addPaymentGatewayMethods($obComponent)
    {
        $obComponent->addDynamicMethod('getPaymentGateway', function() use ($obComponent) {

            //Get payment object
            $obPaymentMethod = $obComponent->obPaymentMethod;
            if(empty($obPaymentMethod) || empty($obPaymentMethod->gateway_id)) {
                return null;
            }

            //Init gateway object
            $obGateway = Omnipay::create($obPaymentMethod->gateway_id);

            $arPaymentGatewayProperty = (array) $obPaymentMethod->gateway_property;
            $obGateway->initialize($arPaymentGatewayProperty);

            return $obGateway;
        });

        $obComponent->addDynamicMethod('sendPaymentGateway', function() use ($obComponent) {

            //Get payment object
            $obPaymentMethod = $obComponent->obPaymentMethod;
            if(empty($obPaymentMethod) || empty($obPaymentMethod->gateway_id)) {
                return null;
            }

            //Get gateway object
            /** @var Omnipay $obGateway */
            $obGateway = $obComponent->getPaymentGateway();
            if(empty($obGateway)) {
                return null;
            }

            //Get payment order data
            $obOrder = $obComponent->obElement;
            if(empty($obOrder)) {
                return null;
            }

            $arPaymentOrderData = (array) $obOrder->payment_data;
            $obCreditCart = new CreditCard($arPaymentOrderData);

            $arGatewayData = [
                'amount'   => $obOrder->getTotalPriceValue(),
                'currency' => $obPaymentMethod->gateway_currency,
                'card'     => $obCreditCart,
            ];

            $obResponse = $obGateway->purchase($arGatewayData)->send();

            return $obResponse;
        });
    }
}