<?php

class Rcb_Recobox_General_Settings {
    public $id;
    public $label;
    public $settings;

    public function __construct() {
        $this->id    = 'general';
        $this->label = "Основные настройки";
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

        require_once( plugin_dir_path( __FILE__ ) . 'views/settings.php' );
    }

    public function save_fields() {
        if ( ! isset( $_POST['rcb_comments_settings_nonce'] ) || ! wp_verify_nonce( $_POST['rcb_comments_settings_nonce'], 'verify_rcb_comments_settings_nonce' ) ) {
            return false;
        }

        $config = array();
        if ( isset( $_POST['widget_id'] ) && ! empty( $_POST['widget_id'] ) ) {
            $config['widget_id'] = sanitize_text_field($_POST['widget_id']);
        }

        if ( isset( $_POST['api_key'] ) ) {
            $config['api_key'] = sanitize_text_field($_POST['api_key']);
        }

        if ( isset( $_POST['sso'] ) ) {
            $config['sso'] = sanitize_text_field(intval($_POST['sso']));
        }

        if ( isset( $_POST['sync'] ) ) {
            $config['sync'] = sanitize_text_field(intval($_POST['sync']));
        }

        if ( isset( $_POST['counter'] ) ) {
            $config['counter'] = sanitize_text_field(intval($_POST['counter']));
        }

        update_option( 'rcb_comments_settings', $config );
        Rcb_Comments_Admin::set_message( 'updated', 'Настройки сохранены.' );

    }
}

new Rcb_Recobox_General_Settings();