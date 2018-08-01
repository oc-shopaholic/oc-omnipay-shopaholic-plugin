<?php namespace Lovata\OmnipayShopaholic\Classes\Event;

use Lang;
use Omnipay\Omnipay;

use Lovata\OmnipayShopaholic\Classes\Helper\PaymentGateway;

use Lovata\OrdersShopaholic\Models\OrderProperty;
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
        $obEvent->listen('backend.form.extendFields', function ($obWidget) {
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
        if (!$obWidget->getController() instanceof PaymentMethods || $obWidget->isNested) {
            return;
        }

        // Only for the Settings model
        if (!$obWidget->model instanceof PaymentMethod || empty($obWidget->model->gateway_id) || !class_exists(Omnipay::class)) {
            return;
        }

        //Get payment gateway list
        $arGatewayList = PaymentGateway::getOmnipayGatewayList();
        if (empty($arGatewayList) || !in_array($obWidget->model->gateway_id, $arGatewayList)) {
            return;
        }

        $this->addGatewayPropertyFields($obWidget->model, $obWidget);
        $this->addUserFieldList($obWidget);
    }

    /**
     * Add gateway property list
     * @param PaymentMethod         $obPaymentMethod
     * @param \Backend\Widgets\Form $obWidget
     */
    protected function addGatewayPropertyFields($obPaymentMethod, $obWidget)
    {
        //Create gateway object
        $obGateway = Omnipay::create($obPaymentMethod->gateway_id);
        if (empty($obGateway)) {
            return;
        }

        //Get default property list for gateway
        $arPropertyList = $obGateway->getDefaultParameters();
        if (empty($arPropertyList)) {
            return;
        }

        //Process property list  for gateway
        foreach ($arPropertyList as $sPropertyName => $arValueList) {
            if (empty($sPropertyName)) {
                continue;
            }

            if (is_array($arValueList)) {
                $obWidget->addTabFields([
                    'gateway_property['.$sPropertyName.']' => [
                        'label'   => $sPropertyName,
                        'tab'     => 'lovata.ordersshopaholic::lang.tab.gateway',
                        'type'    => 'dropdown',
                        'span'    => 'left',
                        'options' => $this->prepareValueList($arValueList),
                    ],
                ]);
            } elseif (is_bool($arValueList)) {
                $obWidget->addTabFields([
                    'gateway_property['.$sPropertyName.']' => [
                        'label'   => $sPropertyName,
                        'tab'     => 'lovata.ordersshopaholic::lang.tab.gateway',
                        'type'    => 'checkbox',
                        'default' => $arValueList,
                        'span'    => 'left',
                    ],
                ]);
            } else {
                $obWidget->addTabFields([
                    'gateway_property['.$sPropertyName.']' => [
                        'label' => $sPropertyName,
                        'tab'   => 'lovata.ordersshopaholic::lang.tab.gateway',
                        'type'  => 'text',
                        'span'  => 'left',
                    ],
                ]);
            }
        }
    }

    /**
     * Prepare value list for backend field
     * @param array $arValueList
     * @return array
     */
    protected function prepareValueList($arValueList)
    {
        if (empty($arValueList) || !is_array($arValueList)) {
            return [];
        }

        if ($arValueList !== array_values($arValueList)) {
            return $arValueList;
        }

        $arResult = [];
        foreach ($arValueList as $sValue) {
            $arResult[$sValue] = $sValue;
        }

        return $arResult;
    }

    /**
     * Add user fields
     * @param \Backend\Widgets\Form $obWidget
     */
    protected function addUserFieldList($obWidget)
    {
        $arPropertyList = $this->getPropertyOptions();
        if (empty($arPropertyList)) {
            return;
        }

        $sSpan = 'left';
        $arFieldList = [];
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
            $arFieldList['gateway_property['.$sFieldName.']'] = $this->getUserFieldData($sFieldName, $sSpan, $arPropertyList);
            $sSpan = $sSpan == 'left' ? 'right' : 'left';
        }

        $obWidget->addTabFields($arFieldList);
    }

    /**
     * @return array
     */
    protected function getPropertyOptions()
    {
        $arResult = (array) OrderProperty::active()->lists('name', 'code');
        if (empty($arResult)) {
            return [];
        }

        foreach ($arResult as &$sName) {
            $sName = Lang::get($sName);
        }

        return $arResult;
    }

    /**
     * Get user field config
     * @param string $sField
     * @param string $sSpan
     * @param array  $arPropertyList
     * @return array
     */
    protected function getUserFieldData($sField, $sSpan, $arPropertyList)
    {
        $sLabel = Lang::get('lovata.ordersshopaholic::lang.field.gateway_field_value', ['field' => $sField]);

        $arResult = [
            'label'       => $sLabel,
            'tab'         => 'lovata.ordersshopaholic::lang.tab.gateway',
            'emptyOption' => 'lovata.toolbox::lang.field.empty',
            'type'        => 'dropdown',
            'span'        => $sSpan,
            'options'     => $arPropertyList,
        ];

        return $arResult;
    }
}
