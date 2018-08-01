<?php namespace Lovata\OmnipayShopaholic\Classes\Helper;

use Event;
use Validator;
use Omnipay\Omnipay;
use Omnipay\Common\CreditCard;

use Lovata\OrdersShopaholic\Classes\Helper\AbstractPaymentGateway;

/**
 * Class PaymentGateway
 * @package Lovata\OmnipayShopaholic\Classes\Helper
 * @author  Andrey Kharanenka, a.khoronenko@lovata.com, LOVATA Group
 */
class PaymentGateway extends AbstractPaymentGateway
{
    const EVENT_GET_PAYMENT_GATEWAY_CLASS = 'shopaholic.payment_method.omnipay.gateway.class';
    const EVENT_GET_PAYMENT_GATEWAY_CANCEL_URL = 'shopaholic.payment_method.omnipay.gateway.cancel_url';
    const EVENT_GET_PAYMENT_GATEWAY_RETURN_URL = 'shopaholic.payment_method.omnipay.gateway.return_url';
    const EVENT_GET_PAYMENT_GATEWAY_PURCHASE_DATA = 'shopaholic.payment_method.omnipay.gateway.purchase_data';

    /** @var \Omnipay\Common\GatewayInterface */
    protected $obGateway;

    /** @var \Omnipay\Common\Message\ResponseInterface */
    protected $obResponse;
    protected $sResponseMessage;

    /**
     * Get omnipay gateway list
     * @return array
     */
    public static function getOmnipayGatewayList() : array
    {
        if (!class_exists(Omnipay::class)) {
            return [];
        }

        $arGatewayList = Omnipay::getFactory()->find();
        if (empty($arGatewayList) || !is_array($arGatewayList)) {
            return [];
        }

        return $arGatewayList;
    }

    /**
     * Get redirect URL
     * @return string
     */
    public function getRedirectURL() : string
    {
        if (empty($this->obResponse)) {
            return '';
        }

        return $this->obResponse->getRedirectUrl();
    }

    /**
     * Get response array
     * @return array
     */
    public function getResponse() : array
    {
        return [];
    }

    /**
     * Get error message from response
     * @return string
     */
    public function getMessage() : string
    {
        if (empty($this->obResponse)) {
            return (string) $this->sResponseMessage;
        }

        return $this->obResponse->getMessage();
    }

    /**
     * Prepare purchase data
     */
    protected function preparePurchaseData()
    {
        if (empty($this->obOrder) || empty($this->obPaymentMethod) || empty($this->obPaymentMethod->gateway_id)) {
            return;
        }

        $this->obGateway = Omnipay::create($this->obPaymentMethod->gateway_id);

        $this->arPurchaseData = [
            'card'          => $this->getCreditCardObject(),
            'amount'        => $this->obOrder->total_price_value,
            'currency'      => $this->obPaymentMethod->gateway_currency,
            'description'   => $this->obOrder->order_number,
            'transactionId' => $this->obOrder->transaction_id,
            'returnUrl'     => $this->getReturnURL(),
            'cancelUrl'     => $this->getCancelURL(),
        ];

        //Get default property list for gateway
       $arPropertyList = $this->obGateway->getDefaultParameters();
        if (empty($arPropertyList)) {
            return;
        }

        foreach ($arPropertyList as $sFieldName => $sValue) {
            $this->arPurchaseData[$sFieldName] = $this->getGatewayProperty($sFieldName);
        }

        $this->extendPurchaseData();
    }

    /**
     * Validate purchase data
     * @return bool
     */
    protected function validatePurchaseData()
    {
        $arRuleSet = [
            'amount'   => 'required',
            'currency' => 'required',
        ];

        $obValidator = Validator::make($this->arPurchaseData, $arRuleSet);
        if ($obValidator->fails()) {
            $this->sResponseMessage = $obValidator->messages()->first();
            return false;
        }

        return true;
    }

    /**
     * Send purchase request to payment gateway
     */
    protected function sendPurchaseData()
    {
        try {
            $this->obResponse = $this->obGateway->purchase($this->arPurchaseData)->send();
        } catch (\Exception $obException) {
            $this->sResponseMessage = $obException->getMessage();
            return;
        }
    }

    /**
     * Process purchase request to payment gateway
     */
    protected function processPurchaseResponse()
    {
        if (empty($this->obResponse)) {
            return;
        }

        $this->bIsRedirect = $this->obResponse->isRedirect();
        $this->bIsSuccessful = $this->obResponse->isSuccessful();
        if ($this->bIsRedirect || $this->bIsSuccessful) {
            $this->setWaitPaymentStatus();
        }
    }

    /**
     * Get new CreditCard object
     * @return null|CreditCard
     */
    protected function getCreditCardObject()
    {
        if (empty($this->obOrder) || empty($this->obPaymentMethod)) {
            return null;
        }

        $arCardData = [];

        //Fill user fields form order properties
        $arUserFieldList = [
            'firstName',
            'lastName',
            'number',
            'expiryMonth',
            'expiryYear',
            'startMonth',
            'startYear',
            'cvv',
            'billingAddress1',
            'billingAddress2',
            'billingCity',
            'billingPostcode',
            'billingState',
            'billingCountry',
            'billingPhone',
            'shippingAddress1',
            'shippingAddress2',
            'shippingCity',
            'shippingPostcode',
            'shippingState',
            'shippingCountry',
            'shippingPhone',
            'company',
            'email',
        ];

        foreach ($arUserFieldList as $sFieldName) {
            //Get filed name from property gateway
            $sOrderField = $this->getGatewayProperty($sFieldName);

            $sValue = $this->getOrderProperty($sOrderField);
            if (empty($sValue)) {
                continue;
            }

            $arCardData[$sFieldName] = $sValue;
        }

        if (empty($arCardData)) {
            return null;
        }

        $obCreditCard = new CreditCard($arCardData);

        return $obCreditCard;
    }

    /**
     * Get cancel URL
     * @return \Illuminate\Contracts\Routing\UrlGenerator|string
     */
    protected function getCancelURL()
    {
        return $this->getRedirectURLForPaymentGateway(self::EVENT_GET_PAYMENT_GATEWAY_CANCEL_URL);
    }

    /**
     * Get return URL
     * @return \Illuminate\Contracts\Routing\UrlGenerator|string
     */
    protected function getReturnURL()
    {
        return $this->getRedirectURLForPaymentGateway(self::EVENT_GET_PAYMENT_GATEWAY_RETURN_URL);
    }

    /**
     * Get redirect URL for purchase request params
     * @param string $sEventName
     * @return \Illuminate\Contracts\Routing\UrlGenerator|string
     */
    protected function getRedirectURLForPaymentGateway($sEventName)
    {
        //Fire event
        $arEventDataList = Event::fire($sEventName, [
            $this->obOrder,
            $this->obPaymentMethod,
            $this->arPurchaseData,
        ]);
        if (empty($arEventDataList)) {
            return url('/');
        }

        //Process event data
        foreach ($arEventDataList as $sURL) {
            if (!empty($sURL) && is_string($sURL)) {
                return $sURL;
            }
        }

        return url('/');
    }

    /**
     * Fire event and extend purchase data
     */
    protected function extendPurchaseData()
    {
        //Fire event
        $arEventDataList = Event::fire(self::EVENT_GET_PAYMENT_GATEWAY_PURCHASE_DATA, [
            $this->obOrder,
            $this->obPaymentMethod,
            $this->arPurchaseData,
        ]);
        if (empty($arEventDataList)) {
            return;
        }

        //Process event data
        foreach ($arEventDataList as $arEventData) {
            if (empty($arEventData) || !is_array($arEventData)) {
                continue;
            }

            foreach ($arEventData as $sField => $sValue) {
                $this->arPurchaseData[$sField] = $sValue;
            }
        }
    }
}