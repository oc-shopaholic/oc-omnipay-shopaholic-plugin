<?php namespace Lovata\OmnipayShopaholic\Classes\Helper;

use Event;
use Illuminate\Support\Facades\Input;
use Redirect;
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
    const SUCCESS_RETURN_URL = 'shopaholic/omnipay/paypal/success/';
    const CANCEL_RETURN_URL = 'shopaholic/omnipay/paypal/cancel/';

    const EVENT_GET_PAYMENT_GATEWAY_CLASS = 'shopaholic.payment_method.omnipay.gateway.class';
    const EVENT_GET_PAYMENT_GATEWAY_CANCEL_URL = 'shopaholic.payment_method.omnipay.gateway.cancel_url';
    const EVENT_GET_PAYMENT_GATEWAY_RETURN_URL = 'shopaholic.payment_method.omnipay.gateway.return_url';
    const EVENT_GET_PAYMENT_GATEWAY_PURCHASE_DATA = 'shopaholic.payment_method.omnipay.gateway.purchase_data';
    const EVENT_GET_PAYMENT_GATEWAY_CARD_DATA = 'shopaholic.payment_method.omnipay.gateway.card_data';

    const EVENT_PROCESS_RETURN_URL = 'shopaholic.payment_method.omnipay.gateway.process_return_url';
    const EVENT_PROCESS_CANCEL_URL = 'shopaholic.payment_method.omnipay.gateway.process_cancel_url';
    const EVENT_PROCESS_NOTIFY_URL = 'shopaholic.payment_method.omnipay.gateway.process_notify_url';

    /** @var \Omnipay\Common\GatewayInterface */
    protected $obGateway;

    /** @var \Omnipay\Common\Message\ResponseInterface */
    protected $obResponse;
    protected $sResponseMessage;

    protected $arCardData = [];

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

        return (string) $this->obResponse->getRedirectUrl();
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

        return (string) $this->obResponse->getMessage();
    }

    /**
     * Process success request
     * @param string $sSecretKey
     * @return \Illuminate\Http\RedirectResponse
     */
    public function processSuccessRequest($sSecretKey)
    {
        $this->initOrderObject($sSecretKey);
        if (empty($this->obOrder) || empty($this->obPaymentMethod)) {
            return Redirect::to('/');
        }

        //Set success status in order
        $this->setSuccessStatus();

        Event::fire(self::EVENT_PROCESS_RETURN_URL, [
            $this->obOrder,
            $this->obPaymentMethod,
        ]);

        //Get redirect URL
        $sRedirectURL = $this->getReturnURL();

        return Redirect::to($sRedirectURL);
    }

    /**
     * Process cancel request
     * @param string $sSecretKey
     * @return \Illuminate\Http\RedirectResponse
     */
    public function processCancelRequest($sSecretKey)
    {
        //Init order object
        $this->initOrderObject($sSecretKey);
        if (empty($this->obOrder) || empty($this->obPaymentMethod)) {
            return Redirect::to('/');
        }

        //Set cancel status in order
        $this->setCancelStatus();

        //Fire event
        Event::fire(self::EVENT_PROCESS_CANCEL_URL, [
            $this->obOrder,
            $this->obPaymentMethod,
        ]);

        //Get redirect URL
        $sRedirectURL = $this->getCancelURL();

        return Redirect::to($sRedirectURL);
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
            'amount'        => $this->obOrder->total_price_data->price_with_tax_value,
            'currency'      => $this->obPaymentMethod->gateway_currency,
            'description'   => $this->obOrder->order_number,
            'transactionId' => $this->obOrder->transaction_id,
            'token'         => $this->obOrder->payment_token,
            'returnUrl'     => url(self::SUCCESS_RETURN_URL.$this->obOrder->secret_key),
            'cancelUrl'     => url(self::CANCEL_RETURN_URL.$this->obOrder->secret_key),
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
        $arPaymentData = (array) $this->obOrder->payment_data;
        $arPaymentData['request'] = $this->arPurchaseData;
        $arPaymentData['request']['card'] = $this->arCardData;

        $this->obOrder->payment_data = $arPaymentData;
        $this->obOrder->save();

        try {
            $this->obResponse = $this->obGateway->purchase($this->arPurchaseData)->send();
        } catch (\Exception $obException) {
            $this->sResponseMessage = $obException->getMessage();
            return;
        }
    }

    /**
     * Send completePurchase request to payment gateway
     */
    protected function sendCompletePurchaseData()
    {
        try {
            $this->obResponse = $this->obGateway->completePurchase($this->arPurchaseData)->send();
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
        if ($this->bIsSuccessful && !$this->bIsRedirect) {
            $this->setSuccessStatus();
        } elseif ($this->bIsRedirect) {
            $this->setWaitPaymentStatus();
            $arPaymentResponse['redirect_url'] = $this->obResponse->getRedirectUrl();
        }

        $arPaymentResponse = (array) $this->obOrder->payment_response;
        $arPaymentResponse['response'] = (array) $this->obResponse->getData();

        $this->obOrder->payment_response = $arPaymentResponse;
        $this->obOrder->payment_token = $this->obResponse->getTransactionReference();
        $this->obOrder->transaction_id = $this->obResponse->getTransactionId();
        $this->obOrder->save();
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

            $this->arCardData[$sFieldName] = $sValue;
        }

        $this->extendCardData();
        if (empty($this->arCardData)) {
            return null;
        }

        $obCreditCard = new CreditCard($this->arCardData);

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
        $arPaymentGateWayPurchaseData = Input::get('payment_gateway_purchase_data');

        if (empty($arPaymentGateWayPurchaseData) || !is_array($arPaymentGateWayPurchaseData)) {
            $arPaymentGateWayPurchaseData = [];
        }

        $this->arPurchaseData = array_merge($this->arPurchaseData, $arPaymentGateWayPurchaseData);

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

    /**
     * Fire event and extend card data
     */
    protected function extendCardData()
    {
        $arPaymentGateWayCardData = Input::get('payment_gateway_card_data');

        if (empty($arPaymentGateWayCardData) || !is_array($arPaymentGateWayCardData)) {
            $arPaymentGateWayCardData = [];
        }

        $this->arCardData = array_merge($this->arCardData, $arPaymentGateWayCardData);

        //Fire event
        $arEventDataList = Event::fire(self::EVENT_GET_PAYMENT_GATEWAY_CARD_DATA, [
            $this->obOrder,
            $this->obPaymentMethod,
            $this->arCardData,
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
                $this->arCardData[$sField] = $sValue;
            }
        }
    }
}
