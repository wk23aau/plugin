<?php

class ArchOpinion_Admin {

    private static $instance;
    const SETTINGS_GROUP = 'archopinion_settings_group';
    const SETTINGS_NAME = 'archopinion_settings';
    const POLICY_FIELD_NAME_PREFIX = 'archopinion_policy_';


    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Actions can be added here if needed upon object construction
    }

    public function add_menu() {
        add_menu_page(
            'ArchOpinion',
            'ArchOpinion',
            'manage_options',
            'archopinion-main',
            array( $this, 'render_new_analysis_page' ),
            'dashicons-analytics', // Or any other appropriate dashicon
            25
        );

        add_submenu_page(
            'archopinion-main',
            'New Analysis',
            'New Analysis',
            'manage_options',
            'archopinion-main', // This makes it the default sub-page
            array( $this, 'render_new_analysis_page' )
        );

        add_submenu_page(
            'archopinion-main',
            'Settings & Policies',
            'Settings & Policies',
            'manage_options',
            'archopinion-settings',
            array( $this, 'render_settings_page' )
        );
    }

    public function render_new_analysis_page() {
        // Ensure this path is correct
        $view_path = ARCHOPINION_PLUGIN_DIR . 'admin/views/new-analysis.php';
        if ( file_exists( $view_path ) ) {
            include $view_path;
        } else {
            echo '<p>Error: New Analysis view file not found.</p>';
        }
    }

    public function render_settings_page() {
        // Ensure this path is correct
        $view_path = ARCHOPINION_PLUGIN_DIR . 'admin/views/settings.php';
        if ( file_exists( $view_path ) ) {
            include $view_path;
        } else {
            echo '<p>Error: Settings view file not found.</p>';
        }
    }

    public function enqueue_scripts( $hook_suffix ) {
        // Only load on plugin pages
        $plugin_pages = array(
            'toplevel_page_archopinion-main',
            'archopinion_page_archopinion-settings'
        );

        if ( in_array( $hook_suffix, $plugin_pages ) ) {
            wp_enqueue_style(
                'archopinion-admin-css',
                plugin_dir_url( dirname( __FILE__ ) ) . 'assets/css/admin.css', // Corrected path
                array(),
                '1.0.0'
            );

            wp_enqueue_script(
                'archopinion-admin-js',
                plugin_dir_url( dirname( __FILE__ ) ) . 'admin/js/admin.js', // Corrected path
                array( 'jquery', 'wp-i18n' ),
                '1.0.0',
                true
            );

            // Localize script for AJAX
            wp_localize_script(
                'archopinion-admin-js',
                'archopinion_ajax',
                array(
                    'ajax_url' => admin_url( 'admin-ajax.php' ),
                    'new_analysis_nonce' => wp_create_nonce( 'archopinion_new_analysis_nonce' ),
                    'remove_policy_nonce' => wp_create_nonce( 'archopinion_remove_policy_nonce' ),
                     'settings_save_nonce' => wp_create_nonce('archopinion_settings_save_nonce')
                )
            );

            // For media uploader
            wp_enqueue_media();
        }
    }

     public function register_settings() {
        register_setting( self::SETTINGS_GROUP, self::SETTINGS_NAME, array( $this, 'sanitize_settings' ) );
        register_setting( self::SETTINGS_GROUP, ArchOpinion_Policy_Manager::POLICY_OPTION_NAME, array( $this, 'sanitize_policies' ) );


        add_settings_section(
            'archopinion_api_settings_section',
            'API Settings',
            null, // Callback for description, if any
            self::SETTINGS_GROUP // Page slug where this section will be shown
        );

        add_settings_field(
            'api_key',
            'Gemini API Key',
            array( $this, 'render_api_key_field' ),
            self::SETTINGS_GROUP, // Page
            'archopinion_api_settings_section' // Section
        );

        // Policy Section
        add_settings_section(
            'archopinion_policy_settings_section',
            'Analysis Policies',
            array($this, 'render_policy_section_description'),
            self::SETTINGS_GROUP
        );

        // Existing policies will be rendered dynamically in the settings page itself.
        // Field for adding a new policy
         add_settings_field(
            'add_new_policy_name',
            'New Policy Name',
            array( $this, 'render_new_policy_name_field' ),
            self::SETTINGS_GROUP,
            'archopinion_policy_settings_section'
        );
        add_settings_field(
            'add_new_policy_text',
            'New Policy Text',
            array( $this, 'render_new_policy_text_field' ),
            self::SETTINGS_GROUP,
            'archopinion_policy_settings_section'
        );
    }

    public function sanitize_settings( $input ) {
        $sanitized_input = array();
        if ( isset( $input['api_key'] ) ) {
            $sanitized_input['api_key'] = sanitize_text_field( $input['api_key'] );
        }
        // Add sanitization for other settings if any
        return $sanitized_input;
    }

    public function sanitize_policies($input) {
        // This function is called when the main form is saved,
        // but policy additions/removals are handled via AJAX or specific form submissions.
        // However, if policies were directly editable in the main settings form (e.g. textareas for each policy),
        // this is where you'd sanitize them.

        // For now, we assume policies are managed by ArchOpinion_Policy_Manager methods.
        // So, we retrieve the current policies from the database to prevent accidental overwrite by an empty array
        // if the form submission doesn't include all policies (which it won't, with our current setup).
        $policy_manager = new ArchOpinion_Policy_Manager();
        $current_policies = $policy_manager->get_policies();

        // If 'archopinion_new_policy_name' and 'archopinion_new_policy_text' are submitted through this form
        // (e.g. if JS is disabled or for a non-AJAX fallback)
        if ( !empty($_POST['archopinion_new_policy_name']) && !empty($_POST['archopinion_new_policy_text']) ) {
            // Security check for nonce if this part is meant to handle direct form submission for adding policies
             if ( ! isset( $_POST['_wpnonce_archopinion_settings_save'] ) || ! wp_verify_nonce( sanitize_text_field(wp_unslash($_POST['_wpnonce_archopinion_settings_save'])), 'archopinion_settings_group-options' ) ) {
                // Nonce is invalid, so don't process the new policy.
                // You might want to add an admin notice here.
                return $current_policies; // Return existing policies without changes
            }

            $new_policy_name = sanitize_text_field( wp_unslash($_POST['archopinion_new_policy_name']) );
            $new_policy_text = sanitize_textarea_field( wp_unslash($_POST['archopinion_new_policy_text']) );
            $policy_key = sanitize_key($new_policy_name);

            if (!empty($policy_key) && !empty($new_policy_text)) {
                $current_policies[$policy_key] = $new_policy_text;
                 // Clear out the submission fields so they don't repopulate if there's an error elsewhere
                $_POST['archopinion_new_policy_name'] = '';
                $_POST['archopinion_new_policy_text'] = '';
            }
        }


        // Handle policy removal if a remove button was clicked (non-AJAX fallback)
        // This requires naming the remove buttons appropriately, e.g., "remove_policy[policy_key]"
        if (isset($_POST['remove_policy_button'])) {
            // Security check for nonce
            if ( ! isset( $_POST['_wpnonce_archopinion_settings_save'] ) || ! wp_verify_nonce( sanitize_text_field(wp_unslash($_POST['_wpnonce_archopinion_settings_save'])), 'archopinion_settings_group-options' ) ) {
                return $current_policies;
            }

            $policy_to_remove_key = sanitize_key(wp_unslash($_POST['remove_policy_button']));
            if (isset($current_policies[$policy_to_remove_key])) {
                unset($current_policies[$policy_to_remove_key]);
            }
        }


        // The $input here would be from the register_setting call if other general policy settings were present.
        // For our dynamic list, $current_policies holds the true state.
        return $current_policies;
    }


    public function render_api_key_field() {
        $options = get_option( self::SETTINGS_NAME );
        $api_key = isset( $options['api_key'] ) ? esc_attr( $options['api_key'] ) : '';
        echo "<input type='text' name='" . self::SETTINGS_NAME . "[api_key]' value='{$api_key}' class='regular-text'>";
    }

    public function render_policy_section_description() {
        echo '<p>Define architectural policies that Gemini will use during its analysis.</p>';
        // List existing policies (handled in settings.php)
    }

    public function render_new_policy_name_field() {
        // This field is for adding a new policy.
        // The name should not be part of the main option array key, but a separate input.
        echo "<input type='text' id='archopinion_new_policy_name_id' name='archopinion_new_policy_name' value='' class='regular-text' placeholder='e.g., Accessibility Standards'>";
        echo "<p class='description'>Unique name for the new policy. This will be converted to a key (e.g. 'accessibility_standards').</p>";
    }

    public function render_new_policy_text_field() {
        echo "<textarea id='archopinion_new_policy_text_id' name='archopinion_new_policy_text' rows='3' class='large-text' placeholder='Describe the policy here...'></textarea>";
    }

}
ArchOpinion_Admin::get_instance(); // Initialize
