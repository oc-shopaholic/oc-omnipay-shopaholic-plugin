<?php namespace Lovata\OmnipayShopaholic;

use Event;
use System\Classes\PluginBase;

use Lovata\OmnipayShopaholic\Classes\Event\ExtendFieldHandler;
use Lovata\OmnipayShopaholic\Classes\Event\PaymentMethodModelHandler;

/**
 * Class Plugin
 * @package Lovata\OmnipayShopaholic
 * @author Andrey Kharanenka, a.khoronenko@lovata.com, LOVATA Group
 */
class Plugin extends PluginBase
{
    public $require = ['Lovata.Toolbox', 'Lovata.Shopaholic', 'Lovata.OrdersShopaholic'];

    /**
     * Boot plugin method
     */
    public function boot()
    {
        $this->addEventListener();
    }

    /**
     * Add event listeners
     */
    protected function addEventListener()
    {
        Event::subscribe(ExtendFieldHandler::class);
        Event::subscribe(PaymentMethodModelHandler::class);
    }
}
