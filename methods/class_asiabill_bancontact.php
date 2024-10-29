<?php
if (! defined ( 'ABSPATH' ))
	exit (); // Exit if accessed directly

class WC_Gateway_Asiabill_Bancontact extends WC_Asiabill_Payment_Gateway {

    var $id;
    var $method_title       = 'AsiaBill Bancontact';
    var $method_description = 'Bancontact 本地支付';
    var $logger;

	public function __construct() {
        parent::__construct('wc_asiabill_bancontact');
	}

    public function init_form_fields() {
        parent::init_form_fields();
        $this->form_fields['title']['default'] = 'Bancontact';
        $this->form_fields['show_logo'] = array(
            'title' => __ ( 'Show logo', 'asiabill' ),
            'type' => 'checkbox',
            'default' => 'yes'
        );
	}


}

?>