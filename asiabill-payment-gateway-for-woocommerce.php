<?php
/**
 * Plugin Name: AsiaBill Payment Gateway for WooCommerce
 * Plugin URI: https://en.asiabill.com
 * Description: Take credit/debit card and other payment methods on your store using Asiabill.
 * Version: 1.2.12
 * Tested up to: 5.8
 * Required PHP version: 7.1
 * Author: AsiaBill
 * Author URI: https://www.asiabill.com
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.zh-cn.html
 */

if (! defined ( 'ABSPATH' ))
	exit (); // Exit if accessed directly

if (! defined ( 'ASIABILL_OL_PAYMENT' )) {
    define ( 'ASIABILL_OL_PAYMENT', 'ASIABILL_OL_PAYMENT' );
} else {
    return;
}

const ASIABILL_MIN_WC_VERSION = '5.8';
const ASIABILL_OL_PAYMENT_VERSION = '1.2.12';
define('ASIABILL_PAYMENT_DIR',rtrim(plugin_dir_path(__FILE__),'/'));
define('ASIABILL_PAYMENT_URL',rtrim(plugin_dir_url(__FILE__),'/'));
const ASIABILL_METHODS = [
    'asiabill_alipay' => 'Alipay',
    'asiabill_creditcard' => 'Credit Card',
    'asiabill_crypto' => 'CryptoPayment',
    'asiabill_directpay' => 'directpay',
    'asiabill_ebanx' => 'Ebanx',
    'asiabill_giropay' => 'giropay',
    'asiabill_ideal' => 'ideal',
    'asiabill_p24' => 'p24',
    'asiabill_paysafecard' => 'paysafecard',
    'asiabill_wechat' => 'WeChatPay',
    'asiabill_koreacard' => 'Korea Local cards',
    'asiabill_kakaopay' => 'KAKAOPAY',
    'asiabill_klarna' => 'klarna',
    'asiabill_bancontact' => 'bcmc',
];

function woocommerce_asiabill_missing_wc_notice() {
    /* translators: 1. URL link. */
    echo '<div class="error"><p><strong>' . sprintf( esc_html__( 'AsiaBill requires WooCommerce to be installed and active. You can download %s here.', 'asiabill' ), '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>' ) . '</strong></p></div>';
}

function woocommerce_asiabill_wc_not_supported() {
    /* translators: $1. Minimum WooCommerce version. $2. Current WooCommerce version. */
    echo '<div class="error"><p><strong>' . sprintf( esc_html__( 'AsiaBill requires WooCommerce %1$s or greater to be installed and active. WooCommerce %2$s is no longer supported.', 'asiabill' ), ASIABILL_MIN_WC_VERSION, WC_VERSION ) . '</strong></p></div>';
}

add_filter('woocommerce_payment_gateways','asiabill_add_gateway',10,1);
function asiabill_add_gateway($methods){
    foreach (ASIABILL_METHODS as $key => $val){
        $val = ucfirst(substr($key,strrpos($key,'_')+1));
        $methods[] = 'WC_Gateway_Asiabill_'.$val;
    }
    return $methods;
}

add_action( 'plugins_loaded', 'woocommerce_asiabill_includes' );
function woocommerce_asiabill_includes(){

    load_plugin_textdomain( 'asiabill', false,   plugin_basename( dirname( __FILE__ ) ) . '/languages/'  );
    wp_enqueue_style( 'asiabill_styles', ASIABILL_PAYMENT_URL.'/assets/css/asiabill.css', [], ASIABILL_OL_PAYMENT_VERSION  );

    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', 'woocommerce_asiabill_missing_wc_notice' );
        return;
    }

    if ( version_compare( WC_VERSION, ASIABILL_MIN_WC_VERSION, '<' ) ) {
        add_action( 'admin_notices', 'woocommerce_asiabill_wc_not_supported' );
        return;
    }

    require_once ASIABILL_PAYMENT_DIR . '/includes/classes/AsiabillIntegration.php';
    require_once ASIABILL_PAYMENT_DIR . '/includes/class-wc-asiabill-payment-token.php';
    require_once ASIABILL_PAYMENT_DIR . '/includes/class-wc-asiabill-customer.php';
    require_once ASIABILL_PAYMENT_DIR . '/includes/class-wc-asiabill-api.php';
    require_once ASIABILL_PAYMENT_DIR . '/includes/class-wc-asiabill-logger.php';
    require_once ASIABILL_PAYMENT_DIR . '/includes/class-wc-asiabill-payment.php';
    require_once ASIABILL_PAYMENT_DIR . '/includes/class-wc-asiabill-order-heandler.php';
    require_once ASIABILL_PAYMENT_DIR . '/includes/class-wc-asiabill-webhook.php';
    require_once ASIABILL_PAYMENT_DIR . '/includes/class-wc-asiabill-blocks.php';

    if( is_array(ASIABILL_METHODS) ){
        foreach (ASIABILL_METHODS as $key => $val){
            $file = ASIABILL_PAYMENT_DIR .'/methods/class_'.$key.'.php';
            if( file_exists($file) ){
                require_once($file);
            }
        }
    }

}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'asiabill_creditcard_payment_gateway_plugin_edit_link' );
function asiabill_creditcard_payment_gateway_plugin_edit_link( $links ){
    return array_merge(
        array(
            'settings' => '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_asiabill_creditcard') . '">'.__( 'Settings', 'woocommerce' ).'</a>'
        ),
        $links
    );
}


add_action( 'woocommerce_admin_order_data_after_order_details', 'asiabill_order_display_admin', 10, 1 );
function asiabill_order_display_admin($order){

    $method = $order->get_payment_method();
    $method_name = str_replace('wc_','',$method);
    $class_name = 'WC_Gateway_'.ucfirst($method_name);

    if( class_exists($class_name) ){
        $class = new $class_name;
        if( $method == $class->id ){
            echo  '<p class="form-field form-field-wide"><label>'.__( 'Payment Method :', 'asiabill' ).'</label>'. esc_html($class->method_title) .'</p>';
            $tradeNo = $order->get_transaction_id();
            echo '<p class="form-field form-field-wide"><label>'.__( 'Ref No :', 'asiabill' ).'</label>'. esc_html($tradeNo) .'</p>';
        }
    }
}

add_action( 'woocommerce_blocks_loaded', 'woocommerce_gateway_dummy_asiabill_block_support' );

function woocommerce_gateway_dummy_asiabill_block_support()
{
    if( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ){
        require_once(ASIABILL_PAYMENT_DIR . '/methods/blocks/class-ab-creditcard-blocks.php');
        require_once(ASIABILL_PAYMENT_DIR . '/methods/blocks/class-ab-checkout-blocks.php');
        add_action(
            'woocommerce_blocks_payment_method_type_registration',
            function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
                $payment_method_registry->register( new WC_Ab_Creditcard_Blocks_Support() );
            }
        );

        foreach( ASIABILL_METHODS as $key => $value ){
            if( $key != 'asiabill_creditcard' ){
                add_action(
                    'woocommerce_blocks_payment_method_type_registration',
                    function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) use ( $key ) {
                        $payment_method_registry->register( new WC_Ab_Checkout_Blocks_Support( $key ) );
                    }
                );
            }
        }


    }
}



?>