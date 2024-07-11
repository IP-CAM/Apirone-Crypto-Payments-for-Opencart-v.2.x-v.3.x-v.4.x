# Apirone Crypto Payments for Opencart 2.x, 3.x, 4.x #

## Description ##

Accept the most popular cryptocurrencies (BTC, LTC, BCH, Doge, etc.) in your store all around the world. Use any crypto supported by the provider to accept coins using the Forwarding payment process.

**Key features:**

* The payment is automatically forwarded from a temporarily generated crypto-address directly into your wallet (temp address associates payment with an exact order)

* The payment gateway has a fixed fee which does not depend on the amount of the order. Small payments are free. [https://apirone.com/pricing](https://apirone.com/pricing)

* White label processing (your online store accepts payments on the store side without redirects, iframes, advertisements, logos, etc.)

* This plugin works well all over the world.

* Tor network support.

## Installation ##

1. Download the build for your Opencart version:
    - Opencart 2 - apirone-crypto-payments.oc2.vX.X.X.ocmod.zip
    - Opencart 3 - apirone-crypto-payments.oc3.vX.X.X.ocmod.zip
    - Opencart 4 - apirone-crypto-payments.oc4.vX.X.X.ocmod.zip

    **Important for Opencart 4** - Rename file to __apirone.ocmod.zip__
2. Go to Extensions » Installer and upload the plugin file.
3. Go to Extensions » Extensions. Choose Payments from the dropdown menu.
4. Click the Install (the green plus) button to install the Apirone Crypto Currency plugin.
5. Click the Edit button.
6 .Enter your cryptocurrency addresses for desired cryptos and switch plugin Status to enable the Plugin settings.

## Update ##

 Opencart v3 update plugin from v1.0.0/v1.0.1 to v1.1.0
- Download the build for Opencart v3 
- Without deleting the old plugin version, install using the admin panel.
- Go to the plugin settings page. 
- All values should be from the previous version. 
- Check the status mapping and, if necessary, set the statuses you use for various invoice states. 

Opencart 4 updating
- Without deleting the installed plugin, unpack the data archive into the extensions/apirone directory


## How does it work? ##

The Buyer adds items into the cart and prepares the order. Using API requests, the store generates crypto (BTC, LTC, BCH, Doge) addresses for payment and shows a QR code. Then, the buyer scans the QR code and pays for the order. This transaction goes to the blockchain. The payment gateway immediately notifies the store about the payment. The store completes the transaction.

## Requirements & License ##

Opencart 2.x, 3.x, 4.x


Tested with PHP 7.4, 8.0

License MIT

## Third Party API & License Information ##

* **API website:** [https://apirone.com](https://apirone.com)
* **API docs:** [https://apirone.com/docs/](https://apirone.com/docs/)
* **Privacy policy:** [https://apirone.com/privacy-policy](https://apirone.com/privacy-policy)
* **Support:** <support@apirone.com>

## Frequently Asked Questions ##

**I will get money in USD, EUR, CAD, JPY, RUR...?**

> No, you will get crypto only. You can enter the crypto address of your trading platform account and convert crypto (BTC, LTC, BCH, Doge) to fiat money at any time.

**How can The Store cancel orders and return bitcoins?**

> This process is fully manual because you will get all payments to your specified wallet. Only you control your money. Contact the Customer, ask for an address and finish the deal. Bitcoin protocol has no refunds, chargebacks, or transaction cancellations. Only the store manager makes decisions on underpaid or overpaid orders whether to cancel the order or return the rest directly to the customers.

**I would like to accept Litecoin only. What should I do?**

> Just enter your LTC address on settings and keep other fields empty.

**Fee:**

>The plugin uses the free Rest API of the Apirone crypto payment gateway. The pricing page [https://apirone.com/pricing](https://apirone.com/pricing)

## Changelog ##

### Version 1.2.6 ###

* Show tbtc for non-auth users when * is set into test customer field.
* Fixed links when oc installed to relative path (not www-root)

### Version 1.2.5 ###

* Added internal QR code generator.
* Added Logging & Debug mode.
* Settings are divided by tabs.
* Added a “Tips and Information” tab.
* Fixed admin page layout.

### Version 1.2.4 ###

* Fixed bug with unsaved settings for Opencart version 4.0.2.3

### Version 1.2.3 ###

* Fixed bug with displaying small amounts in exponential format for the currency selector

### Version 1.2.2 ###

* Added the ability to pay for downloads and subscriptions

### Version 1.2.1 ###

* Added support opencart up to 4.0.2.2 version

### Version 1.2.0 ###

* The plugin is switched to a new fee plan.
  Now the fee is not fixed but charged in amount of 1% of the transfer.
