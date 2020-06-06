# WooCommerce Simple Installment Gateway #

## Description ##

### Adds a simple installment payment gateway ###

The following screenshots illustrate what this plugin can do:

### Checkout page with new payment option ###

The checkout shows the installment payment gateway and calculates the monthly rates based on the payment gateway settings and the cart total. Also adds a handling fee.

![simple-installation-screenshot-1](simple-installation-screenshot-1.jpg)

### Thank you page & customer email ###

Adds the installment plan with number of installments and monthly amount on the thank you page after the order details.

![simple-installation-screenshot-2](simple-installation-screenshot-2.jpg)

Adds the installment plan to the admin and customer email, with the number of installments and monthly amount after the order details.

![simple-installation-screenshot-3](simple-installation-screenshot-3.jpg)

### Admin orders page ###

Adds the installment plan with number of installments and monthly amount to the admin screen.

![simple-installation-screenshot-4](simple-installation-screenshot-4.jpg)

### Payment Gateway settings page ###

The screenshot below shows all available options:

- You can set a general customer message before the installment plan info, which is the number of monthly rates and the monthly rate amount.
- You can set a minimum cart total as a threshold for this payment gateway to shop on the checkout.
- You can activiate for guests. By default only customers who are logged in and who have one completeted order may see this payment gateway.
- You can set the number of monthly installments.
- You can set an extra fee for the handling of installments.
- By default the order status is in waiting.
- The admin notice by default is hidden.

![simple-installation-screenshot-5](simple-installation-screenshot-5.jpg)

## Installation ##
Download ZIP file and unpack the plugin directory.

You can install it by placing it directly into the `/wp-content/plugins/`directory of your WordPress installation and then activate it from the "Plugins" screen, or you can go to "Plugins" - "Install" - "Upload Plugin" and upload it, and then activate it.

Go then to "WooCommerce" - "Settings" and activate this payment option under "Checkout".

## Changelog ##

### 1.0.0 ###
First version: tested with Woocommerce 4.2 and WordPress 5.4.1
