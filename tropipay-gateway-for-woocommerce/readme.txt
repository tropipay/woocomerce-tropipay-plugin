=== Tropipay Gateway for WooCommerce ===
Contributors: tropipay, tropipaydev
Donate link: https://www.tropipay.com/
Tags: woocommerce, tropipay, payment gateway, gateway, payments
Requires at least: 5.8
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 5.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Accept credit card, debit card and TropiPay balance payments securely and modernly in your WooCommerce store, compatible with Gutenberg Blocks.

== Description ==

**Tropipay Gateway for WooCommerce** is the official payment gateway to connect your e-commerce store with the international payment platform **TropiPay**. 

It allows your customers to pay in an extremely fast, intuitive and secure way through credit or debit cards, as well as directly using their TropiPay account balance.

This version 5.0.0 has been redesigned from scratch complying with the strictest performance standards and is **100% compatible with the new WooCommerce Block Checkout (Gutenberg)**.

### Main Features:
* **Advanced Block Checkout**: Fully compatible with the modern block-based checkout experience in WooCommerce.
* **Smart Account Synchronization**: Connect your store with your TropiPay account and securely load your active destination accounts with a single click.
* **Dynamic and Reactive Form**: Smooth administrative interface that interactively hides the fee fields if you do not want to charge them.
* **Enterprise-Class Performance**: Intelligent reuse of generated payment links (Paylinks) to optimize receipt page load time to 0 ms.
* **Maximum Security**: Verification of cryptographic signatures (SHA256) and injection of nonces to protect communication against attacks and duplicate transactions.
* **Complete Idempotency**: Async and robust webhook that correctly manages stock and order states in a secure and seamless way.

== External services ==

This plugin connects to the TropiPay payment platform API (either sandbox environment at `https://sandbox.tropipay.me` or production environment at `https://www.tropipay.com` or `https://tropipay.com`) to process payment transactions and sync merchant accounts.

- **What the service is and what it is used for**: TropiPay is an international payment service provider. This plugin uses the TropiPay API to generate payment links (Paylinks) for checkout redirection and retrieve the merchant's active destination accounts.
- **What data is sent and when**: During the checkout process, when the customer chooses TropiPay and proceeds to payment, the plugin sends payment details (amount, currency, unique order reference, concept, lang, return and notification URLs) and billing information (name, last name, email, phone, country, address, city, state, postal code). When synchronizing accounts in the admin panel, the plugin sends API client credentials (Client ID and Client Secret) to retrieve account information.
- **Links to terms of use and privacy policy**:
  - TropiPay Terms of Service: https://www.tropipay.com/terms
  - TropiPay Privacy Policy: https://www.tropipay.com/privacy

== Installation ==

1. Upload the `tropipay` plugin folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **WooCommerce > Settings > Payments** and select **Tropipay**.
4. Configure your environment (Sandbox or Live) and enter your TropiPay Client ID and Client Secret.
5. Click "Save changes" and then click "Synchronize with TropiPay" to securely load your available destination accounts.
6. Ready! Your store is now ready to accept international payments with TropiPay.

== Frequently Asked Questions ==

= Do I need a TropiPay account? =
Yes, you need to have an active business account in TropiPay. You can register at [tropipay.com](https://www.tropipay.com/) to obtain your API keys (Client ID and Client Secret).

= Is it compatible with the new WooCommerce block checkout? =
Absolutely! Tropipay Gateway for WooCommerce includes native integration with WooCommerce Blocks, ensuring your checkout process looks modern and sophisticated.

= What happens if I delete the plugin? =
The plugin includes a clean uninstallation mechanism that safely removes all configured keys and synchronized accounts from your database to avoid leaving orphan data.

== Screenshots ==

1. Configuration of the payment gateway in the WooCommerce administration panel.
2. Smart synchronization of destination accounts through direct API connection.
3. View of the modern block checkout with card and balance payment options.

== Changelog ==

= 5.0.0 =
* Major release.
* Full and native compatibility with WooCommerce Blocks (Block Checkout).
* Implementation of smart asynchronous account synchronization via AJAX.
* Complete refactoring of the asynchronous webhook, making it 100% secure, robust, and idempotent.
* Addition of Paylinks persistence and reuse, optimizing page load performance.
* Integration of a clean uninstallation script (`uninstall.php`).