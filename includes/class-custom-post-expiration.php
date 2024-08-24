<?php

class Custom_Post_Expiration {

    protected $loader;
    protected $plugin_name;
    protected $version;

    public function __construct() {
        if ( defined( 'CUSTOM_POST_EXPIRATION_VERSION' ) ) {
            $this->version = CUSTOM_POST_EXPIRATION_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'custom-post-expiration';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function load_dependencies() {
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-custom-post-expiration-loader.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-custom-post-expiration-i18n.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-custom-post-expiration-admin.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-custom-post-expiration-public.php';

        $this->loader = new Custom_Post_Expiration_Loader();
    }

    private function set_locale() {
        $plugin_i18n = new Custom_Post_Expiration_i18n();
        $this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
    }

    private function define_admin_hooks() {
        $plugin_admin = new Custom_Post_Expiration_Admin( $this->get_plugin_name(), $this->get_version() );

        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
        $this->loader->add_action( 'add_meta_boxes', $plugin_admin, 'add_expiration_meta_box' );
        $this->loader->add_action( 'save_post', $plugin_admin, 'save_expiration_datetime' );
        $this->loader->add_action( 'admin_menu', $plugin_admin, 'add_plugin_admin_menu' );
        $this->loader->add_action( 'admin_init', $plugin_admin, 'register_settings' );
        $this->loader->add_action( 'cpen_daily_expiration_check', $plugin_admin, 'daily_expiration_check' );
        $this->loader->add_action( 'cpen_expiration_check', $plugin_admin, 'expiration_check' );
    }

    private function define_public_hooks() {
        $plugin_public = new Custom_Post_Expiration_Public( $this->get_plugin_name(), $this->get_version() );
    }

    public function run() {
        $this->loader->run();
    }

    public function get_plugin_name() {
        return $this->plugin_name;
    }

    public function get_loader() {
        return $this->loader;
    }

    public function get_version() {
        return $this->version;
    }
}