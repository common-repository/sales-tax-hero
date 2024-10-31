<?php
/*
    Plugin name: Sales Tax Compliance - Sales Tax Hero
    Description: Adds real time sales tax calculation to your woocommerce store without breaking the bank. 
    version: 1.1
    Requires at least: 5.0
    Requires PHP: 7.0
    Author: Dustin Gunter
    Author URI: salestaxhero.com
    License: GPL v2 or later
    License URI: https://www.gnu.org/licenses/gpl-2.0.html
    text-domain: sales-tax-hero
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}
update_option('salestaxhero_getting_started_dismissed', false);
update_option('salestaxhero_api_key_validated', '');
update_option('salestaxhero_offer_taken', '');
update_option('salestaxhero_real_time_activated', false);
update_option('salestaxhero_subscription_validated', false);


function salestaxhero_check_environment(){
    require_once ABSPATH . 'wp-admin/includes/plugin.php';

    // Check PHP version.
    if ( version_compare( phpversion(), '5.5', '<' ) ) {
        add_action( 'admin_notices', array( $this, 'salestaxhero_php_version_notice' ) );

        return false;
    }
    
    // Check WooCommerce version.
    if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
        add_action( 'admin_notices', array( $this, 'salestaxhero_woocommerce_required_notice' ) );

        return false;
    } else if ( ! defined( 'WC_VERSION' ) || version_compare( WC_VERSION, '3.0', '<' ) ) {
        add_action( 'admin_notices', array( $this, 'salestaxhero_woocommerce_version_notice' ) );

        return false;
    }
    
    return true;
}

function salestaxhero_detect_plugin_conflicts() {
    if ( class_exists( 'WC_TaxJar' ) ) {
        // TaxJar.
        add_action( 'admin_notices', array( $this, 'salestaxhero_taxjar_conflict_notice' ) );
        return true;
    } elseif ( class_exists( 'WC_AvaTax_Loader' ) ) {
        // WooCommerce AvaTax.
        add_action( 'admin_notices', array( $this, 'salestaxhero_avatax_conflict_notice' ) );
        return true;
    } elseif ( class_exists( 'WC_Connect_Loader' ) && 'yes' === get_option( 'wc_connect_taxes_enabled' ) ) {
        // WooCommerce Services Automated Taxes.
        add_action( 'admin_notices', array( $this, 'salestaxhero_woocommerce_services_notice' ) );
        return true;
    }

    return false;
}

add_action( 'init', 'salestaxhero_check_environment' );
add_action( 'init', 'salestaxhero_detect_plugin_conflicts' );

/**
 * Settings Page
 **/
function salestaxhero_add_settings_tab( $settings_tabs ) {
    $settings_tabs['sales_tax_hero'] = __( 'Sales Tax Hero', 'woocommerce' );
    return $settings_tabs;
}
add_filter( 'woocommerce_settings_tabs_array', 'salestaxhero_add_settings_tab', 50 );

function salestaxhero_settings_tab() {
    woocommerce_admin_fields( salestaxhero_get_settings() );
}
add_action( 'woocommerce_settings_tabs_sales_tax_hero', 'salestaxhero_settings_tab' );

function salestaxhero_update_settings() {

    $salestaxhero_nonce = isset($_POST['salestaxhero_update_settings_nonce']) ? sanitize_text_field( wp_unslash( $_POST['salestaxhero_update_settings_nonce'] ) ) : null;

    if (!$salestaxhero_nonce || !wp_verify_nonce($_POST['salestaxhero_update_settings_nonce'], 'salestaxhero_update_settings_action')) {
        // Nonce is not set or invalid, so do not proceed with the update.
        set_transient('salestaxhero_settings_error', 'Security check failed. Please try again.', 45);
        return;
    }

    $api_key = isset( $_POST['salestaxhero_api_key'] ) ? sanitize_text_field( $_POST['salestaxhero_api_key'] ) : '';
    $company_id = isset( $_POST['salestaxhero_company_id'] ) ? sanitize_text_field( $_POST['salestaxhero_company_id'] ) : '';

    $data = array(
        'company_id' => $company_id,
        'public_key' => $api_key,
        'action' => 'sth_validate_api_key'
    );

    $response = wp_remote_post('https://app.salestaxhero.com/engine/data-manager.php', array(
        'method' => 'POST',
        'headers' => array(
            'Content-Type' => 'application/json',
        ),
        'body' => json_encode($data),
        'timeout' => 15,
    ));

    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        set_transient('sth_settings_error', "WP Error: API Key validation failed: $error_message", 45);
    } else {
        $response_body = wp_remote_retrieve_body($response);

        if ($response_body === 'true') {
            woocommerce_update_options(salestaxhero_get_settings());
        } else {
            // If validation fails, notify the user
            set_transient('salestaxhero_settings_error', "API Key validation failed: Entered Data invalid or company is not subscribed to service.", 45);
        }
    }
}
add_action( 'woocommerce_update_options_sales_tax_hero', 'salestaxhero_update_settings' );

add_action('admin_notices', 'salestaxhero_show_settings_errors');

function salestaxhero_show_settings_errors() {
    if ($error = get_transient('salestaxhero_settings_error')) {
        echo '<div class="notice notice-error"><p>' . esc_html($error) . '</p></div>';
        delete_transient('salestaxhero_settings_error');
    }
}

function salestaxhero_get_settings() {
    $settings = array(
        'custom_image' => array(
            'name' => __( 'Instructional Image', 'woocommerce' ),
            'type' => 'custom_image',
            'desc' => __( 'This image helps to illustrate the settings.', 'woocommerce' ),
            'id'   => 'salestaxhero_custom_image'
        ),
        'section_title' => array(
            'name'     => __( 'Sales Tax Hero Settings', 'woocommerce' ),
            'type'     => 'title',
            'desc'     => '',
            'id'       => 'salestaxhero_settings_section_title'
        ),
        'custom_image' => array(
            'name' => __( 'Instructional Image', 'woocommerce' ),
            'type' => 'custom_image',
            'desc' => __( 'This image helps to illustrate the settings.', 'woocommerce' ),
            'id'   => 'salestaxhero_custom_image'
        ),
        
        'api_key' => array(
            'name' => __( 'API Key', 'woocommerce' ),
            'type' => 'text',
            'desc' => __( 'Enter your Sales Tax Hero API Key', 'woocommerce' ),
            'id'   => 'salestaxhero_api_key',
            'desc_tip' => true,
        ),
        'company_id' => array(
            'name' => __( 'Company ID', 'woocommerce' ),
            'type' => 'text',
            'desc' => __( 'Enter your Sales Tax Hero Company ID', 'woocommerce' ),
            'id'   => 'salestaxhero_company_id',
            'desc_tip' => true,
        ),
         'nonce_field' => array(
            'type' => 'nonce_field',
        ),
        'section_end' => array(
             'type' => 'sectionend',
             'id' => 'salestaxhero_settings_section_end'
        ),
    );
    
    $api_key_set = get_option('salestaxhero_api_key');
    $company_id_set = get_option('salestaxhero_company_id');
    
    if (empty($api_key_set) || empty($company_id_set)) {
        $settings['custom_paragraph'] = array(
            'name' => __( 'Instructions', 'woocommerce' ),
            'type' => 'custom_paragraph',
            'desc' => __( 'Sales Tax Hero requires an API Key and Company ID to function. Follow the required steps below to get started:<br/>', 'woocommerce' ),
            'id'   => 'salestaxhero_custom_paragraph'
        );
    } else {
        $settings['custom_button'] = array(
            'name' => __( 'Manage Subscription', 'woocommerce' ),
            'type' => 'custom_button',
            'id'   => 'salestaxhero_custom_button'
        );
    }
    
    
    return $settings;
}

// Handle the rendering of the nonce field
add_filter('woocommerce_admin_field_nonce_field', 'salestaxhero_render_nonce_field');

function salestaxhero_render_nonce_field($value) {
    wp_nonce_field('salestaxhero_update_settings_action', 'salestaxhero_update_settings_nonce');
}

function salestaxhero_custom_settings_field_type( $value ) {
    switch ( $value['type'] ) {
        case 'custom_paragraph':
            // Output your paragraph here
            echo '<p>' . esc_html($value['desc'])  . '</p>';
            echo "<ol>";
            echo "<li>If you haven't already sign up to Sales Tax Hero <a href='https://buy.stripe.com/test_aEUdR4em6dPQg2A5kl' target='_blank'>here</a>. Subscriptions are managed by Stripe.</li>";
            echo "<li>Check your inbox for the link to create your password. Be sure to check Junk and  Spam folders</li>";
            echo "<li>Once you have created your password, login to Sales Tax Hero <a href='https://app.salestaxhero.com/login.php' target='_blank'>here</a>.</li>";
            echo "<li>Once you are logged in you will find your Company ID <a href='https://app.salestaxhero.com/settings.php' target='_blank'>here</a>.</li>";
            echo "<li>Add the states you want to charge sales tax in <a href='https://app.salestaxhero.com/settings.php#economic_nexus' target='_blank'>here</a>.</li>";
            echo "<li>You can then create an API Key <a href='https://app.salestaxhero.com/settings.php#settings_manage_api_keys' target='_blank'>here</a>.</li>";
            echo "<li>Submit both the Company ID and the API Key below for validation. Once saved, you are all set up!</li>";
            echo "</ol>";
            echo '<a href="https://buy.stripe.com/test_aEUdR4em6dPQg2A5kl" target="_blank" class="button-primary">Manage Subscription</a>';
            break;
        case 'custom_image':
            // Output your image here
        $plugin_url = plugins_url('/', __FILE__);
        $image_path = 'assets/sth_logo_no_slogan.png';
        $image_url = $plugin_url . $image_path;
            echo '<img src="' . esc_url( $image_url ) . '" alt="' . esc_attr( 'Instructional Image' ) . '" style="max-width: 250px;"><br style="clear:both;">';
            break;
    }
}
add_action( 'woocommerce_admin_field_custom_paragraph', 'salestaxhero_custom_settings_field_type' );
add_action( 'woocommerce_admin_field_custom_image', 'salestaxhero_custom_settings_field_type' );

function salestaxhero_render_custom_button( $value ) {
    printf(
        '<a href="https://billing.stripe.com/p/login/9AQeV92ic9sA4RWdQQ" class="button">%s</a><br><br><strong>Please Note:</strong> Sales Tax Hero uses Stripe to manage subscriptions to the service.',
        $value['name'],
        __( 'Manage Subscription', 'woocommerce' )
    );
}

add_action( 'woocommerce_admin_field_custom_button', 'salestaxhero_render_custom_button' );

/**
 * Notice displayed when the TaxJar plugin is activated.
 */
function salestaxhero_taxjar_conflict_notice() {
    printf(
        '<div class="notice notice-error"><p>%s</p></div>',
        esc_html(
            '<strong>Sales Tax Hero is inactive.</strong> Sales Tax Hero cannot be used alongside the TaxJar plugin. Please deactivate TaxJar to use Sales Tax Hero.',
            'sales-tax-hero'
        )
    );
}

/**
 * Notice displayed when the WooCommerce AvaTax plugin is activated.
 */
function salestaxhero_avatax_conflict_notice() {
    printf(
        '<div class="notice notice-error"><p>%s</p></div>',
        esc_html( 
            '<strong>Sales Tax Hero is inactive.</strong> Sales Tax Hero cannot be used alongside the WooCommerce AvaTax plugin. Please deactivate WooCommerce AvaTax to use Sales Tax Hero.',
            'sales-tax-hero'
        )
    );
}

/**
 * Notice displayed when the WooCommerce Services Automated Tax service
 * is enabled.
 */
function salestaxhero_woocommerce_services_notice() {
    printf(
        '<div class="notice notice-error"><p>%s</p></div>',
        esc_html( 
            '<strong>Sales Tax Hero is inactive.</strong> Sales Tax Hero cannot be used alongside WooCommerce Services Automated Taxes. Please disable automated taxes to use Sales Tax Hero.',
            'sales-tax-hero'
        )
    );
}

/**
 * Notice displayed when the installed version of PHP is not compatible.
 */
function salestaxhero_php_version_notice() {
    printf(
        '<div class="notice notice-error"><p>%s</p></div>',
        esc_html( '<strong>PHP needs to be updated.</strong> Sales Tax Hero requires PHP 5.5+.', 
            'sales-tax-hero' 
        )
    );
}

/**
 * Notice displayed if WooCommerce is not installed or inactive.
 */
function salestaxhero_woocommerce_required_notice() {
    printf(
        '<div class="notice notice-error"><p>%s</p></div>',
        esc_html(
            '<strong>WooCommerce not detected.</strong> Please install or activate WooCommerce to use Sales Tax Hero.',
            'sales-tax-hero'
        )
    );
}

/**
 * Notice displayed if the installed version of WooCommerce is not compatible.
 */
function salestaxhero_woocommerce_version_notice() {
    printf(
        '<div class="notice notice-error"><p>%s</p></div>',
        esc_html(
            '<strong>WooCommerce needs to be updated.</strong> Sales Tax Hero requires WooCommerce 3.0.0+.',
            'sales-tax-hero'
        )
    );
}



function salestaxhero_add_tax_fee( $cart ) {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
        return;
    }

    $tax_amount = salestaxhero_do_lookup( $cart );
    $cart->add_fee( 'Sales Tax', $tax_amount, true, '' );
}

add_action( 'woocommerce_cart_calculate_fees', 'salestaxhero_add_tax_fee', 99, 1 );

function salestaxhero_admin_notices() {
    // Check if the notice has been dismissed
    if (get_option('salestaxhero_notice_dismissed') == '1') {
        return;
    }

    // Check if API Key and Company ID are empty
    $api_key_empty = get_option('salestaxhero_api_key') === '';
    $company_id_empty = get_option('salestaxhero_company_id') === '';

    if ($api_key_empty || $company_id_empty) {
        ?>
        <div class="notice notice-warning is-dismissible sth-admin-notice">
            <p>
                Sales Tax Hero requires a subscription to provide tax calculation service. 
                <a href="admin.php?page=wc-settings&tab=sales_tax_hero">Get Started</a>
            </p>
        </div>
        <?php
    }
}
add_action('admin_notices', 'salestaxhero_admin_notices');

function salestaxhero_dismiss_admin_notice() {
    update_option('sth_notice_dismissed', '1');
}
add_action('wp_ajax_sth_dismiss_admin_notice', 'sth_dismiss_admin_notice');



function salestaxhero_do_lookup( $cart ) {
    $lookup = array();
    
    $sth_api_key    = get_option("salestaxhero_api_key");
    $sth_company_id = get_option("salestaxhero_company_id");
    
     if (empty($sth_api_key) || empty($sth_company_id)) {
        return 0; 
    }
    
    $destination    = salestaxhero_get_order_destination( $cart );
    $origin         = salestaxhero_get_store_origin();
    $line_items     = salestaxhero_get_line_items_from_cart( $cart );
    
    //if no destination set, bail
    if(empty($destination['state'])){
        return 0;
    }
    
    //Time to do lookup
    $lookup = array(
        "sth_api_key" => $sth_api_key,
        "sth_company_id" => $sth_company_id,
        "destination" => $destination,
        "origin" => $origin,
        "line_items" => $line_items,
        "action" => "sth_woo_do_lookup"
    );

    $json_data = wp_json_encode($lookup);

    $response = wp_remote_post('https://app.salestaxhero.com/engine/lookup-manager.php', array(
        'method' => 'POST',
        'headers' => array(
            'Content-Type' => 'application/json',
        ),
        'body' => $json_data,
        'timeout' => 15, 
        'sslverify' => true, 
    ));

    // Check for errors
    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        echo "Something went wrong: " . esc_html($error_message);
        return 0;
    } else {
        $response_code = wp_remote_retrieve_response_code($response);

        if ($response_code == 200) {
            $response_body = json_decode($response['body'], true);
            if(is_null($response_body)) return 0;

            $sth_meta_data_json = wp_json_encode($response['body']);

            $_SESSION['salestaxhero_meta_data'] = $sth_meta_data_json;
            $total_tax = $response_body['total_tax'];
            
            return $total_tax;
        }

        //return 0;
    }
    
}

function salestaxhero_get_store_origin(){
    $store_address     = get_option( 'woocommerce_store_address' );
    $store_address_2   = get_option( 'woocommerce_store_address_2' );
    $store_city        = get_option( 'woocommerce_store_city' );
    $store_postcode    = get_option( 'woocommerce_store_postcode' );

    // The country and state separated by a colon
    $store_raw_country = get_option( 'woocommerce_default_country' );

    // Split the country and state
    $split_country = explode( ":", $store_raw_country );

    // Country and state separated:
    $store_country = isset( $split_country[0] ) ? $split_country[0] : '';
    $store_state   = isset( $split_country[1] ) ? $split_country[1] : '';
    
    $origin = array(
        "address_1" => $store_address,
        "address_2" => $store_address_2,
        "city" => $store_city,
        "state" => $store_state, // This coming up blank
        "postalCode" => $store_postcode,
        "country" => $store_country
    );
    
    return $origin;
}
/**
 * Get line items from WooCommerce cart, including shipping.
 *
 * @param WC_Cart $cart The WooCommerce cart object.
 * @return array The line items and shipping details.
 */
function salestaxhero_get_line_items_from_cart($cart) {
    if (!is_a($cart, 'WC_Cart')) {
        return array(); // Return empty array if not a valid cart object
    }

    $line_items = array();

    // Loop through cart items
    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        $product = $cart_item['data'];
        $single_price = $product->get_price();
        $subtotal = $single_price * $cart_item['quantity'];

        $sth_taxable_classification = get_post_meta($product->get_id(), '_sth_taxable_classification', true);
        $is_taxable = (empty($sth_taxable_classification) || $sth_taxable_classification === 'taxable') ? 'true' : 'false';
        $is_virtual = $product->is_virtual();
        
        $line_items[] = array(
            'name' => $product->get_name(),
            'sku'  => $product->get_sku(),
            'quantity' => $cart_item['quantity'],
            'price' => $single_price,
            'line_subtotal' => $subtotal,
            'taxable' => $is_taxable,
            'virtual' => $is_virtual
        );
    }

    // Add a line for the shipping charge
    $shipping_total = $cart->get_shipping_total();
    if ($shipping_total > 0) {
        $line_items[] = array(
            'name' => 'Shipping',
            'sku'  => 'SHIPPING',
            'quantity' => 1,
            'price' => $shipping_total,
            'line_subtotal' => $shipping_total,
            'taxable' => false,
            'virtual' => false
        );
    }

    return $line_items;
}

function salestaxhero_get_order_destination( $cart ) {
    $shipping_address = array(
        'first_name' => $cart->get_customer()->get_shipping_first_name(),
        'last_name'  => $cart->get_customer()->get_shipping_last_name(),
        'address_1'  => $cart->get_customer()->get_shipping_address(),
        'address_2'  => $cart->get_customer()->get_shipping_address_2(),
        'city'       => $cart->get_customer()->get_shipping_city(),
        'state'      => $cart->get_customer()->get_shipping_state(),
        'postalCode' => $cart->get_customer()->get_shipping_postcode(),
        'country'    => $cart->get_customer()->get_shipping_country()
    );
    
    return $shipping_address;
}

function salestaxhero_update_order_tax_as_fee( $order_id ) {
    $order = wc_get_order( $order_id );

    if ( ! $order ) {
        return;
    }

    $fee = $order->get_fees();
    foreach ( $fee as $item ) {
        if ( 'Sales Tax' === $item->get_name() ) {
            $tax_amount = $item->get_total();
            update_post_meta( $order_id, '_sth_sales_tax', $tax_amount );
            break;
        }
    }
}

add_action( 'woocommerce_checkout_update_order_meta', 'salestaxhero_update_order_tax_as_fee', 10, 1 );


// Add the custom tab
function salestaxhero_add_custom_product_data_tab($tabs) {
    $tabs['salestaxhero_taxable_classification'] = array(
        'label'   => __('Taxable Classification', 'woocommerce'),
        'target'  => 'salestaxhero_taxable_classification_product_data',
        'class'   => array('show_if_simple', 'show_if_variable'),  // can adjust to which product types you want it to show up for
    );
    return $tabs;
}
add_filter('woocommerce_product_data_tabs', 'salestaxhero_add_custom_product_data_tab');

// 2. Display the fields inside the tab
function salestaxhero_custom_product_data_fields() {
    global $post;

    $product = wc_get_product($post->ID);
    $current_value = $product->get_meta('_salestaxhero_taxable_classification', true);

    echo '<div id="salestaxhero_taxable_classification_product_data" class="panel woocommerce_options_panel">';

// Add the nonce field here
    wp_nonce_field('salestaxhero_save_custom_product_data', 'salestaxhero_product_nonce');
    
    woocommerce_wp_select(array(
        'id'            => '_salestaxhero_taxable_classification',
        'label'         => __('Taxable Classification', 'woocommerce'),
        'options'       => array(
            'taxable'     => __('Taxable', 'woocommerce'),
            'non-taxable' => __('Non-Taxable', 'woocommerce'),
        ),
        'value'         => $current_value,
        'desc_tip'      => true,
        'description'   => __('Select the taxable classification for this product.', 'woocommerce'),
    ));

    echo '</div>';
}
add_action('woocommerce_product_data_panels', 'salestaxhero_custom_product_data_fields');

//Save the custom field value
function salestaxhero_save_custom_product_data_fields($post_id) {
    
    $salestaxhero_product_nonce = isset($_POST['salestaxhero_product_nonce']) ? sanitize_text_field( wp_unslash( $_POST['salestaxhero_product_nonce'] )) : null;


    if (!$salestaxhero_product_nonce || !wp_verify_nonce($salestaxhero_product_nonce, 'salestaxhero_save_custom_product_data')) {
        return;
    }
    
    $product = wc_get_product($post_id);

    if(isset($_POST['_salestaxhero_taxable_classification'])){
        $selected_value = sanitize_text_field($_POST['_salestaxhero_taxable_classification']);
    } else {
        $selected_value = "taxable";
    }

    $product->update_meta_data('_salestaxhero_taxable_classification', esc_attr($selected_value));
    
    $product->save();
}
add_action('woocommerce_process_product_meta', 'salestaxhero_save_custom_product_data_fields');

add_action('woocommerce_new_order', 'salestaxhero_add_custom_meta_data_to_order', 10, 1);
function salestaxhero_add_custom_meta_data_to_order($order_id) {
    // Check if the session variable exists and sanitize it.
    $sth_meta_data = isset($_SESSION['salestaxhero_meta_data']) ? sanitize_text_field($_SESSION['salestaxhero_meta_data']) : '';

    // Proceed only if there is metadata to save.
    if (!empty($sth_meta_data)) {
        // Update the order meta with sanitized data.
        update_post_meta($order_id, 'salestaxhero_meta_data', $sth_meta_data);
        
        // Optionally clear the session variable after use.
        unset($_SESSION['salestaxhero_meta_data']);
    }
}


//Order Completed Status
add_action('woocommerce_order_status_completed', 'salestaxhero_submit_order');

function salestaxhero_submit_order($order_id) {
    // Get the order object
    $order_data = array();
    $order = wc_get_order($order_id);
	
	//check if already submitted. 
	$is_commited = get_post_meta($order_id, '_salestaxhero_order_commited', true);
    if ($is_commited) {
		echo "Order is Committed - STH";
        return;
    }
	
	$sth_api_key 	= get_option("salestaxhero_api_key");
	$sth_company_id = get_option("salestaxhero_company_id");
	$origin			= salestaxhero_get_store_origin();
	$destination 	= salestaxhero_get_order_destination_address( $order );
	$line_items  	= salestaxhero_get_order_line_items( $order );
	$sth_meta_data  = get_post_meta($order_id, 'salestaxhero_meta_data');

    $order_data = array(
		"action" => "sth_capture_order",
        "sth_api_key" 	=> $sth_api_key,
		"sth_company_id" => $sth_company_id,
		"OrderID" => $order->get_order_number(),
		"TransactionsDate" => salestaxhero_get_order_creation_date($order),
		"AuthorizedDate" => salestaxhero_get_order_creation_date($order),
		"CapturedDate" => date('Y-m-d'),
		"destination" => $destination,
		"origin" => $origin,
		"line_items" => $line_items,
		"meta_data" => $sth_meta_data,
    );

    $response = wp_remote_post('https://app.salestaxhero.com/engine/lookup-manager.php', array(
        'method' => 'POST',
        'headers' => array(
            'Content-Type' => 'application/json',
        ),
        'body' => json_encode($order_data),
        'timeout' => 15,
    ));

    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
		wc_add_notice(__('An error occurred while processing your order. Please try again.', 'sales-tax-hero'), 'error');
		set_transient('sth_api_error', $error_message, 45);
    } else {
        if (wp_remote_retrieve_response_code($response) == 200) {
            update_post_meta($order_id, '_sth_order_commited', true);
        } else {
			$error_message = wp_remote_retrieve_response_message($response);
			wc_add_notice(__('An error occurred while processing your order. Please try again.', 'sales-tax-hero'), 'error');
			set_transient('sth_api_error', $error_message, 45);
		}
    }
}

function salestaxhero_get_order_destination_address($order) {
    if (!is_a($order, 'WC_Order')) {
        return false; // Return false if the provided argument is not a valid WC_Order object
    }

    $destination_address = array(
        'first_name'    => $order->get_shipping_first_name(),
        'last_name'     => $order->get_shipping_last_name(),
        'company'       => $order->get_shipping_company(),
        'address_1'     => $order->get_shipping_address_1(),
        'address_2'     => $order->get_shipping_address_2(),
        'city'          => $order->get_shipping_city(),
        'state'         => $order->get_shipping_state(),
        'postalCode'    => $order->get_shipping_postcode(),
        'country'       => $order->get_shipping_country()
    );

    return $destination_address;
}

function salestaxhero_get_order_line_items($order) {
    if (!is_a($order, 'WC_Order')) {
        return false; // Return false if the provided argument is not a valid WC_Order object
    }

    $line_items_array = array();
    $line_items = $order->get_items();

	$counter = 0;
	
    foreach ($line_items as $item_id => $item_data) {
        // Get product from item
        $product 		= $item_data->get_product();
		$sku 			= $product->get_sku();
		$product_name 	= $product->get_name();

        $line_items_array[] = array(
			'CartItemIndex' 	 => $counter,
            'product_identifier' => !empty($sku) ? $sku : substr($product_name, 0, 50),
            'product_tax_code'   => 0,
            'product_price'      => $product->get_price(),
            'product_quantity'   => $item_data->get_quantity(),
            'product_tax_rate'   => 0.00,
			'product_tax_amount' => 0.00,
        );
		//TODO: Update to support exemption certs.
		$counter++;
    }
	
	//Include Shipping in line items
    $shipping_total = $order->get_shipping_total();
    if ($shipping_total > 0) {
        $shipping_method_title = $order->get_shipping_method(); 

        $line_items_array[] = array(
			'CartItemIndex'		 => $counter,
            'product_identifier' => $shipping_method_title,
            'product_tax_code'   => 0,
            'product_price'=> $shipping_total,
            'product_quantity'   => 1,
			'product_tax_amount' => 0.00
        );
    }

    return $line_items_array;
}

add_action('woocommerce_order_status_changed', 'salestaxhero_uncommit_order', 10, 3);

function salestaxhero_uncommit_order($order_id, $old_status, $new_status) {
    // Bail early if the order is not marked as cancelled
    if ($new_status !== 'cancelled') {
        return;
    }

	//bail early its already been uncommited.	
    $is_commited = get_post_meta($order_id, '_salestaxhero_order_commited', true);
    if (!$is_commited) {
        return;
    }
	
	//update to false to prevent redundant requests
    update_post_meta($order_id, '_salestaxhero_order_commited', false);

    $order = wc_get_order($order_id);
    $sth_api_key 	= get_option("salestaxhero_api_key");
    $sth_company_id = get_option("salestaxhero_company_id");

    $order_data = array(
        "sth_api_key" 	=> $sth_api_key,
        "sth_company_id" => $sth_company_id,
        "OrderID" => $order->get_order_number(),
        "action" => "sth_uncommit_order"
    );

    // Send request to the endpoint
    $response = wp_remote_post('https://app.salestaxhero.com/engine/lookup-manager.php', array(
        'method' => 'POST',
        'headers' => array(
            'Content-Type' => 'application/json',
        ),
        'body' => json_encode($order_data),
        'timeout' => 15,
    ));

    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
		wc_add_notice(__('An error occurred while processing your order. Please try again.', 'sales-tax-hero'), 'error');
		set_transient('sth_api_error', esc_html($error_message), 45);
    } else {
        $response_code = wp_remote_retrieve_response_code($response);
		if($response_code !== 200) {
			$error_message = wp_remote_retrieve_response_message($response);
			wc_add_notice(__('An error occurred while processing your order. Please try again.', 'sales-tax-hero'), 'error');
			set_transient('sth_api_error', esc_html($error_message), 45);
		} else {
			update_post_meta($order_id, '_salestaxhero_order_commited', false);
		}
    }
}


function salestaxhero_get_order_creation_date($order) {
    if (!is_a($order, 'WC_Order')) {
        return false; // Return false if the provided argument is not a valid WC_Order object
    }

    $date_created = $order->get_date_created();
    return $date_created->date('Y-m-d H:i:s');  // Format as 'YYYY-MM-DD HH:MM:SS'
}
register_uninstall_hook(__FILE__, 'salestaxhero_plugin_uninstall');
register_deactivation_hook(__FILE__, 'salestaxhero_plugin_uninstall');

function salestaxhero_plugin_uninstall() {
    //clean up
    delete_option('salestaxhero_api_key');
    delete_option('salestaxhero_company_id');
}

