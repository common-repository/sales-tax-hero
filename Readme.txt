=== Sales Tax Calculator - Sales Tax Hero ===
Contributors: sthdevelopment
Donate link: 
Tags: sales tax hero, tax, taxes, sales tax, sales tax compliance
Requires at least: 5.0
Tested up to: 6.5.2
Stable tag: 1.1
Requires PHP: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Sales tax calculations and reporting made simple by connecting your WooCommerce store to Sales Tax Hero.

== Description ==

Sales Tax Hero integrates your WooCommerce store with [Sales Tax Hero](https://www.salestaxhero.com) to automate sales tax calculations, and reporting.

With a single monthly subscription fee and support for product level tax exemptions and tax exempt customers baked in, Sales Tax Hero is the simpliest and cost effective sales tax automation solution for WooCommerce.

**Key Features:**

* Affordable — Sales Tax Hero's [simple pricing](https://salestaxhero.com) provides the best value in the industry. 
* Accurate sales tax calculations — Calculate sales tax in real time for every state, county, city, and special jurisdiction in the US. Rates are updated automatically so you never have to worry.
* Multi-state support — Whether your business has presence in dozens of states or just one, Sales Tax hero make it easy.
* Exemption certificates — (Coming Soon) Optionally enable tax exemptions and collect exemption certificates from exempt customers. 

== Requirements ==

*An active Sales Tax hero subscription.
*An API key and company ID provided by Sales Tax Hero.

== Integration == 

Our plugin utilizes our own tax lookup and management functionalities. This integration allows users to retrieve tax-related information directly from SalesTaxHero's database.

== Using Sales Tax Hero Integration == 

Our plugin integrates with Sales Tax Hero, a custom service that provides sales tax calculation. Follow the instructions below to set up and use Sales Tax Hero with this plugin.

== Important Notes ==

*Usage of Sales Tax Hero may be subject to our terms of service and any applicable pricing plans. Please review our documentation for more information.
*The performance of our plugin may be affected by the responsiveness of Sales Tax Hero.

== Support and Resources == 

For assistance with Sales Tax Hero integration, please refer to the documentation on the settings screen of the plugin or contact our support team at https://www.salestaxhero.com/contact-us/.

== Supported WooCommerce Extensions ==

Need us to add compatibility with an extension? Drop us a line at info@salestaxhero.com.

== Installation ==

= Step 1: Create a Sales Tax Hero Account =

If you have not yet registered for Sales Tax Hero, click [here](https://salestaxhero.com/) to get started. Registration is fast and simple.

= Step 2: Configure your Sales Tax Hero account =

Now that you have created your Sales Tax Hero account, there are a few important matters to take care of. Please log in to your Sales Tax Hero account and complete all of the items below.

1. **Create an API Key.** While logged in, go to [Settings -> Manage API Keys](https://app.salestaxhero.com/settings.php#settings-manage-locations). Click Create New API Key and follow the prompts.
2. **Get Your Company ID** While logged in, go to [Settings](https://app.salestaxhero.com/settings.php). The first field within the Company Details. You will need this to activate the plugin.
3. **Select your tax states.** Navigate to [Settings -> Manage Economic Nexus](https://app.salestaxhero.com/settings.php#economic_nexus). Click "Add New State" at the bottom of the section and choose your state from the menu, as well as the reporting frequency. Make sure you have selected all of your nexus states so that the automatic calculation covers all the appropriate areas.
4. **Add Information to Plugin.** Return to your plugin settings and provide the API key and the company id in the fields. Once you save this information, you are ready to start calculating tax.

= Step 3: Install and Activate the Sales Tax Hero Plugin =

To install Sales Tax Hero, log in to your WordPress dashboard, navigate to the Plugins menu, and click "Add New."

In the search field type "Sales Tax Hero," then click "Search Plugins." Once you've found our plugin, you can view details about it such as the point release, rating, and description. Most importantly of course, you can install it! Just click "Install Now," and WordPress will take it from there. Finally, click "Activate" to finish the installation process.

= Step 4: Configure Sales Tax Hero =

1. Navigate to WooCommerce > Settings > Sales Tax Hero in the WordPress dashboard.
2. Enter your Sales Tax Hero Client ID and API Key in the relevant fields, then click "Verify Settings" to validate your API credentials. You can find your API ID and API Key under [Settings](https://app.salestaxhero.com/settings) in the Sales Tax Hero dashboard. After entering and validating your credentials, click **Save changes** to import your business [locations](https://app.salestaxhero.com/settings) from Sales Tax Hero.
4. Click **Save changes** to finalize your changes.

= Step 5: Configure Your Products =

A new taxability menu will appear within the Woocommerce product data. At this time Sales Tax Hero only supports taxable and non-taxable settings. These will be expanded in the next version.

By default, all products in your store will be configured to ship from the Shipping Origin Addresses you've selected in your Woocommerce settings page. 

= Step 7: Testing =

Now that Sales Tax Hero is installed, you should perform several test transactions to ensure that everything is working properly. To do so, add some items to your cart and go through the checkout process to make sure sales tax is calculated and applied. After checking out, don't forget to go to the WooCommerce -> Orders page to mark your test order as "completed."

While testing, you may review your transactions by logging in to Sales Tax Hero and navigating to the "Transactions" menu.

= Step 8: Your Ready! =

You have tested your website and verified that Sales Tax Hero is working properly

Please feel free to [contact us](https://salestaxhero.com/contact) if you need help with any step of this process.

== Frequently Asked Questions ==

= What does Sales Tax Hero cost? =

Please review Sales Tax Hero [Pricing](https://www.salestaxhero.com/pricing) page for details on the cost of Sales Tax Hero.

= Does the plugin support recurring payments? =

Not at this time, though this feature has been planned for a future version.

= What versions of WooCommerce and WordPress are supported? =

Sales Tax Hero supports WooCommerce 5.0+ and WordPress 5.5+.

== Changelog ==

See [Releases](https://github.com/erelan888/salestaxhero/releases).

== Upgrade Notice ==

= 1.0 =
* Tested up to WordPress 6.4.3
= 1.1 =
* Tested up to WordPress 6.5.2