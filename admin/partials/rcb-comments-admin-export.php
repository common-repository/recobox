<?php

class Rcb_Recobox_General_Export {
    public $id;
    public $label;
    public $settings;

    public function __construct() {
        $this->id    = 'export';
        $this->label = "Загрузка комментариев в Recobox";
        add_filter( 'rcb_comments_settings_tabs_array', array( $this, 'add_tabs' ) );
        add_action( 'rcb_comments_settings_' . $this->id, array( $this, 'show_fields' ) );
        add_action( 'rcb_comments_save_' . $this->id, array( $this, 'save_fields' ) );
    }

    public function add_tabs( $tabs ) {
        $tabs[ $this->id ] = $this->label;

        return $tabs;
    }

    public function show_fields() {
        $this->settings = get_option( 'rcb_comments_settings' );

        require_once( plugin_dir_path( __FILE__ ) . 'views/export.php' );
    }

    public function save_fields() {
        if ( ! isset( $_POST['rcb_comments_export_nonce'] ) || ! wp_verify_nonce( $_POST['rcb_comments_export_nonce'], 'verify_rcb_comments_export_nonce' ) ) {
            return false;
        }
    }
}

new Rcb_Recobox_General_Export();