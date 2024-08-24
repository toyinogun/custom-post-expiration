<?php

class Custom_Post_Expiration_i18n {
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'custom-post-expiration',
            false,
            dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
        );
    }
}