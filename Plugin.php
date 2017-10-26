<?php namespace Lovata\OmnipayShopaholic;

use System\Classes\PluginBase;

/**
 * Class Plugin
 * @package Lovata\OmnipayShopaholic
 * @author Andrey Kharanenka, a.khoronenko@lovata.com, LOVATA Group
 */
class Plugin extends PluginBase
{
    public $require = ['Lovata.Toolbox', 'Lovata.Shopaholic', 'Lovata.OrdersShopaholic'];
}
