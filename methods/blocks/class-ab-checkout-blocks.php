<?php

class WC_Ab_Checkout_Blocks_Support extends WC_Wc_Asiabill_Blocks
{
    function __construct( $name )
    {
        $this->name = 'wc_'.$name;
        $name = str_replace('asiabill_','',$name);
        $class = 'WC_Gateway_Asiabill_' . ucfirst($name);
        $this->gateway = new $class();
    }
}