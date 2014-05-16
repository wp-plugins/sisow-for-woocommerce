=== WooCommerce - Sisow Payment Options ===
Plugin Name: Sisow Payment for WooCommerce
Contributors: sisow
Donate link: http://www.sisow.nl
Tags: Sisow, iDEAL, Creditcard, WooCommerce, Payment, MisterCash, SofortBanking, OverBoeking, Ebill, Giftcard, PayPal, Visa, Mastercard, Maestro
Requires at least: 3.0.1
Tested up to: 3.9
Stable tag: 3.5.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Sisow Payment methods for WooCommerce 1.6 and WooCommerce 2.X

== Description ==

This plug-in contains all the payment methods from Sisow for WooCommerce.
The plug-in works on WooCommerce 1.6 and WooCommerce 2.x.

For now this plug-in contains the following payment methods:
<ul>
<li>CreditCard</li>
<li>Ebill</li>
<li>iDEAL</li>
<li>Maestro</li>
<li>Mastercard</li>
<li>MisterCash</li>
<li>SofortBanking</li>
<li>OverBoeking</li>
<li>PayPal</li>
<li>Visa</li>
<li>Webshop GiftCard</li>
</ul>

== Installation ==

1. Upload the zip file with the Wordpress plug-in manager
2. Activate the desired payment methods in your plug-in manager
3. Configure the plug-in methods in the WooCommerce configuration

To make use of this plug-in you need a account on www.sisow.nl

== Frequently Asked Questions ==

= When I start a Transaction I get a error message -1 =

You didn't enter a MerchantID in the configuration

= When I start a Transaction I get a error message -2 =

You didn't enter a MerchantKey in the configuration

= When I start a Transaction I get a error message -4 =

The order total is lower than 0.45

= When I start a Transaction I get a error message -7 =

You didn't choose a payment method in the checkout

= When I start a Transaction I get a error message -8 =

Error on your server, check or CURL is enabled. If it is installed reboot your server.

= When I start a Transaction I get a error message -9 =

A general error, please send the TA code to support@sisow.nl

= When I start a Transaction I get a error message -9, TA3410 =

Enable the Test Mode in you Sisow Account under "Mijn Profiel" tab "Geavanceerd". Enable the first checkbox "Testen met behulp van simulator (toestaan)".

= When I recieve an TA.... error =

Send an e-mail to support@sisow.nl with the given TA code.

== Screenshots ==

1. The added plug-ins
2. The configuration in WooCommerce

== Changelog ==
= 3.5.1 =
* Fix: error handling

= 3.5.0 =
* Added: Maestro/MasterCard/Visa

= 3.3.17 =
* Fix: correct result page for iDEAL

= 3.3.16 =
* Fix: correct result page for ebill/overboeking

= 3.3.8 =
* Fix: redirect from ebill/overboeking

= 3.3.0 =
* Added CreditCard

= 3.2.2 =
* Fix for sequential order numbers
* Removed Sisow Ecare

= 3.2.1 =
* Fix for Sisow Ecare

= 3.2.0 =
* Added Sisow Ecare
* Addes Sisow PayPal

== Upgrade Notice ==

= 3.5.0 =
Added: Maestro/MasterCard/Visa

= 3.3.16 =
Fix: correct result page for ebill/overboeking

= 3.3.8 =
Correct redirect for Sisow Ebill/OverBoeking

= 3.2.2 =
You can use this plug-in with sequential order numbers

= 3.2.1 =
Birthday wasn't parsed correctly

= 3.2.0 =
Added new payment methods
