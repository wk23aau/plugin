<?php

class ArchOpinion_Policy_Manager {

    private $policies;
    const POLICY_OPTION_NAME = 'archopinion_analysis_policies';

    public function __construct() {
        $this->load_policies();
    }

    private function load_policies() {
        $this->policies = get_option( self::POLICY_OPTION_NAME, array() );
    }

    public function get_policies() {
        return $this->policies;
    }

    public function add_policy( $policy_name, $policy_text ) {
        if ( empty( $policy_name ) || empty( $policy_text ) ) {
            return new WP_Error( 'missing_fields', 'Policy name and text are required.' );
        }
        $this->policies[ sanitize_key( $policy_name ) ] = sanitize_textarea_field( $policy_text );
        return $this->save_policies();
    }

    public function remove_policy( $policy_name ) {
        $sanitized_name = sanitize_key( $policy_name );
        if ( isset( $this->policies[ $sanitized_name ] ) ) {
            unset( $this->policies[ $sanitized_name ] );
            return $this->save_policies();
        }
        return false;
    }

    public function get_policy_by_name( $policy_name ) {
        $sanitized_name = sanitize_key( $policy_name );
        return isset( $this->policies[ $sanitized_name ] ) ? $this->policies[ $sanitized_name ] : null;
    }

    private function save_policies() {
        return update_option( self::POLICY_OPTION_NAME, $this->policies );
    }

    public function get_all_policies_for_prompt() {
        if (empty($this->policies)) {
            return "No specific architectural policies are currently defined. Analyze based on general best practices.";
        }

        $prompt_string = "Please analyze the image based on the following architectural policies:\n\n";
        foreach ($this->policies as $name => $text) {
            $prompt_string .= "- " . esc_html( str_replace('_', ' ', $name) ) . ": " . esc_html( $text ) . "\n";
        }
        $prompt_string .= "\nConsider these policies in your analysis.";
        return $prompt_string;
    }
}
