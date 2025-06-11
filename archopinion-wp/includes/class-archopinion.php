<?php

class ArchOpinion {

    private static $instance;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Initialize plugin
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

        // Load dependencies
        $this->load_dependencies();

        // Register AJAX handlers if Ajax_Handlers class exists
        if (class_exists('ArchOpinion_Ajax_Handlers')) {
            $ajax_handler = new ArchOpinion_Ajax_Handlers();
            add_action( 'wp_ajax_archopinion_new_analysis', array( $ajax_handler, 'handle_new_analysis' ) );
        }

        // Hook for admin initialization
        if (class_exists('ArchOpinion_Admin')) {
             add_action('admin_init', array(ArchOpinion_Admin::get_instance(), 'register_settings'));
        }
    }

    private function load_dependencies() {
        require_once ARCHOPINION_PLUGIN_DIR . 'includes/class-gemini-client.php';
        require_once ARCHOPINION_PLUGIN_DIR . 'includes/class-report-generator.php';
        require_once ARCHOPINION_PLUGIN_DIR . 'includes/class-policy-manager.php';
        require_once ARCHOPINION_PLUGIN_DIR . 'includes/class-ajax-handlers.php';
        require_once ARCHOPINION_PLUGIN_DIR . 'admin/class-admin.php';
    }


    public function add_admin_menu() {
        if (class_exists('ArchOpinion_Admin')) {
            ArchOpinion_Admin::get_instance()->add_menu();
        }
    }

    public function enqueue_admin_scripts() {
        if (class_exists('ArchOpinion_Admin')) {
            ArchOpinion_Admin::get_instance()->enqueue_scripts();
        }
    }
}
