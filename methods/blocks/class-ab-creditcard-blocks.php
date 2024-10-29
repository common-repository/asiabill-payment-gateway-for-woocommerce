<?php

class WC_Ab_Creditcard_Blocks_Support extends WC_Wc_Asiabill_Blocks {

    public function __construct()
    {
        $this->name = 'wc_asiabill_creditcard';
        $this->gateway = new WC_Gateway_Asiabill_Creditcard();
    }

    public function get_payment_method_script_handles()
    {
        $script_path = '/assets/js/blocks/asiabill/index.js';
        $script_asset_path = ASIABILL_PAYMENT_DIR . 'assets/js/frontend/blocks.asset.php';

        $script_asset = file_exists( $script_asset_path )
            ? require($script_asset_path)
            : array(
                'dependencies' => array(),
                'version'      => ASIABILL_OL_PAYMENT_VERSION
            );

        $handle = 'wc-ab-asiabilll-blocks';

        if( $this->settings['checkout_mode'] == '1' ){
            $script_asset['dependencies'][] = 'asiabill_payment';
        }

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

    public function get_payment_method_data()
    {
        $params = $this->gateway->javascript_params();

        $icons = [];

        foreach( ['visa','master_card','jcb','ae','discover','diners'] as  $value ){
            if( $this->settings[$value.'_logo'] === 'yes' ){
                $icons[] = [
                    'id'  => 'ab-card-' . $value,
                    'src' => ASIABILL_PAYMENT_URL . '/assets/images/' . $value . ( $value == 'master_card'?'':'_card' ) .'.png',
                    'alt' => $value
                ];
            }
        }

        return array_merge( [
            'title'          => $this->settings['title'],
            'description'    => $this->settings['description'],
            'checkout_model' => $this->settings['checkout_mode'],
            'icons'          => $icons,
            'supports'       => array_filter( $this->gateway->supports, [$this->gateway, 'supports'] )
        ], $params );
    }

}

