# Omnipay for Shopaholic plugin

Request to payment gateway is automatically send after order was created.
You can use [events](https://github.com/lovata/oc-shopaholic-plugin/wiki/EventList#payment-gateways) to fully integrate your project and payment gateway.

## Installation guide

Add **ignited/laravel-omnipay** package and  omnipay gateway packages to the composer.json of your project.
```
{
    "require": [
        ...
       "ignited/laravel-omnipay": "2.*",
        "omnipay/authorizenet": "^2.4",
        "omnipay/buckaroo": "^2.1",
        "omnipay/cardsave": "^2.1",
        "omnipay/coinbase": "^2.0",
        "omnipay/common": "^2.5",
        "omnipay/dummy": "^2.1",
        "omnipay/eway": "^2.2",
        "omnipay/firstdata": "^2.3",
        "omnipay/gocardless": "^2.2",
        "omnipay/manual": "^2.2",
        "omnipay/migs": "^2.2",
        "omnipay/mollie": "^3.1",
        "omnipay/multisafepay": "^2.3",
        "omnipay/netaxept": "^2.3",
        "omnipay/netbanx": "^2.2",
        "omnipay/payfast": "^2.1",
        "omnipay/payflow": "^2.2",
        "omnipay/paymentexpress": "^2.2",
        "omnipay/paypal": "^2.6",
        "omnipay/pin": "^2.2",
        "omnipay/sagepay": "^2.3",
        "omnipay/securepay": "^2.1",
        "omnipay/stripe": "^2.4",
        "omnipay/targetpay": "^2.2",
        "omnipay/worldpay": "^2.2",
        "collizo4sky/omnipay-2checkout": "^1.4"
    ],
```

Execute below at the root of your project.
```
composer update
```
You can also install only packages and its dependencies without updating other packages by specifying the package.
```
composer require ignited/laravel-omnipay
```

## License

© 2018, [LOVATA Group, LLC](https://github.com/lovata) under [GNU GPL v3](https://opensource.org/licenses/GPL-3.0).

Developed by [Andrey Kharanenka](https://github.com/kharanenka).
