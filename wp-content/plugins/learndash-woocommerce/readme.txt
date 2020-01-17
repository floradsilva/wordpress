=== WooCommerce for LearnDash ===
Author: LearnDash
Author URI: https://learndash.com
Plugin URI: https://learndash.com/add-on/woocommerce/ 
LD Requires at least: 2.5
Slug: learndash-woocommerce
Tags: integration, woocommerce,
Requires at least: 4.9
Tested up to: 4.9.7
Requires PHP: 7.0
Stable tag: 1.5.0

Integrate LearnDash LMS with WooCommerce.

== Description ==

Integrate LearnDash LMS with WooCommerce.

WooCommerce is the most popular shopping cart software for WordPress. Most WordPress themes are compatible with WooCommerce. This add-on allows you to sell your LearnDash created courses with the WooCommerce shopping cart.

= Integration Features = 

* Easily map courses to products
* Associate one, or multiple courses to a single product
* Automatic course access removal
* Works with any payment gateway
* Works with WooCommerce Subscription

See the [Add-on](https://learndash.com/add-on/woocommerce/) page for more information.

== Installation ==

If the auto-update is not working, verify that you have a valid LearnDash LMS license via LEARNDASH LMS > SETTINGS > LMS LICENSE. 

Alternatively, you always have the option to update manually. Please note, a full backup of your site is always recommended prior to updating. 

1. Deactivate and delete your current version of the add-on.
1. Download the latest version of the add-on from our [support site](https://support.learndash.com/article-categories/free/).
1. Upload the zipped file via PLUGINS > ADD NEW, or to wp-content/plugins.
1. Activate the add-on plugin via the PLUGINS menu.

== Changelog ==

= 1.5.0 =
* Addded version arg to enqueue scripts function
* Addded retroactive course access checker tool
* Addded filter hook for manual payment methods check
* Addded filter hook for subscription_course_access functions
* Added enroll users on payment complete hook
* Added un-autocomplete transaction if it has physical product
* Added filter and function to allow user to remove course access on subscription billing cycle completion
* Added new translations and udpate class
* Added 'is user enrolled to course' check before enroll new course access
* Added associate related course of variation product to buyer
* Added related courses field to product variation and add save data hook function
* Added version plugin constants
* Added auto complete course order
* Updated functions naming
* Updated orders for bacs and cheque payment methods to prevent auto-completion
* Updated make add-on compatible only with WC 3.0 and above

View the full changelog [here](https://www.learndash.com/add-on/woocommerce/).