<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

abstract class WC_Wc_Asiabill_Blocks extends AbstractPaymentMethodType
{

    /**
     * The gateway instance.
     *
     * @var object
     */
    protected $gateway;

    /**
     * Payment method name/id/slug.
     *
     * @var string
     */
    protected $name;


    /**
     * Initializes the payment method type.
     */
    public function initialize()
    {
        $this->settings = get_option( 'woocommerce_' . $this->name . '_settings', [] );
        if( $this->name == 'wc_asiabill_kakaopay' ){
            $this->settings['title'] = (new WC_Gateway_Asiabill_Kakaopay)->method_description;
        }
    }

    /**
     * Returns if this payment method should be active. If false, the scripts will not be enqueued.
     *
     * @return boolean
     */
    public function is_active()
    {
        return $this->gateway->is_available();
    }

    /**
     * Returns an array of scripts/handles to be registered for this payment method.
     *
     * @return array
     */
    public function get_payment_method_script_handles()
    {
        $script_path = '/assets/js/blocks/' . $this->name . '.js';

        $script_asset = array(
            'dependencies' => array(
                'react',
                'wc-blocks-registry',
                'wc-settings',
                'wp-html-entities',
                'wp-i18n',
                'wp-polyfill'
            ),
            'version'      => ASIABILL_OL_PAYMENT_VERSION
        );

        $handle = 'wc-ab-' . str_replace( '_', '-', $this->name ) . '-blocks';

        wp_register_script(
            $handle,
            ASIABILL_PAYMENT_URL . $script_path,
            $script_asset['dependencies'],
            $script_asset['version'],
            true
        );


        if( function_exists( 'wp_set_script_translations' ) ){
            wp_set_script_translations( $handle, 'woocommerce-gateway-' . $this->name, ASIABILL_PAYMENT_URL . 'languages/' );
        }

        return [$handle];
    }


    /**
     * Returns an array of key=>value pairs of data made available to the payment methods script.
     *
     * @return array
     */
    public function get_payment_method_data()
    {
        return [
            'title'       => $this->get_setting( 'title' ),
            'description' => $this->get_setting( 'description' ),
            'supports'    => array_filter( $this->gateway->supports, [$this->gateway, 'supports'] )
        ];
    }

}