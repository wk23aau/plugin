<?php

class ArchOpinion_Ajax_Handlers {

    public function handle_new_analysis() {
        // Check nonce for security
        check_ajax_referer( 'archopinion_new_analysis_nonce', 'nonce' );

        // Get API key from options
        $options = get_option( 'archopinion_settings' );
        $api_key = isset( $options['api_key'] ) ? $options['api_key'] : '';

        if ( empty( $api_key ) ) {
            wp_send_json_error( array( 'message' => 'API Key is not configured.' ) );
            return;
        }

        if ( ! isset( $_FILES['analysis_image'] ) ) {
            wp_send_json_error( array( 'message' => 'No image file provided.' ) );
            return;
        }

        // Handle file upload
        // WordPress recommends using wp_handle_upload for security.
        if ( ! function_exists( 'wp_handle_upload' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        $uploaded_file = $_FILES['analysis_image'];
        $upload_overrides = array( 'test_form' => false );
        $movefile = wp_handle_upload( $uploaded_file, $upload_overrides );

        if ( $movefile && ! isset( $movefile['error'] ) ) {
            $image_path = $movefile['file']; // Path to the uploaded image
            $image_url = $movefile['url'];   // URL of the uploaded image

            // Get image data as base64 encoded string for Gemini API
            $image_data = base64_encode( file_get_contents( $image_path ) );

            // Prepare the prompt
            $policy_manager = new ArchOpinion_Policy_Manager();
            $policies_prompt = $policy_manager->get_all_policies_for_prompt();
            $custom_prompt_text = isset($_POST['custom_prompt']) ? sanitize_textarea_field($_POST['custom_prompt']) : '';
            $full_prompt = $policies_prompt;
            if (!empty($custom_prompt_text)) {
                $full_prompt .= "\n\nAdditionally, consider the following: " . $custom_prompt_text;
            }


            // Call Gemini API
            $gemini_client = new ArchOpinion_Gemini_Client( $api_key );
            $analysis_result = $gemini_client->analyze_image( $image_data, $full_prompt );

            if ( isset( $analysis_result['error'] ) ) {
                wp_delete_file( $image_path ); // Clean up uploaded file
                wp_send_json_error( array( 'message' => 'Error from Gemini API: ' . $analysis_result['error'] ) );
            } else {
                // Generate PDF report
                $report_generator = new ArchOpinion_Report_Generator();
                $pdf_content = $report_generator->generate_pdf_report( $analysis_result['analysis'], $image_path );

                if (is_wp_error($pdf_content)) {
                    wp_delete_file( $image_path ); // Clean up
                    wp_send_json_error( array( 'message' => 'Failed to generate PDF report: ' . $pdf_content->get_error_message() ) );
                    return;
                }

                // Save report or make it available for download
                // For this example, let's save it to the uploads directory and provide a link.
                $upload_dir = wp_upload_dir();
                $report_filename = 'ArchOpinion_Report_' . time() . '.pdf';
                $report_path = $upload_dir['path'] . '/' . $report_filename;
                $report_url = $upload_dir['url'] . '/' . $report_filename;

                if ( file_put_contents( $report_path, $pdf_content ) === false ) {
                     wp_delete_file( $image_path ); // Clean up
                     wp_send_json_error( array( 'message' => 'Failed to save PDF report.' . $report_path ) );
                     return;
                }

                wp_delete_file( $image_path ); // Clean up the original uploaded image after successful report generation

                wp_send_json_success( array(
                    'message' => 'Analysis complete.',
                    'analysis' => $analysis_result['analysis'],
                    'report_url' => $report_url,
                    'image_url' => $image_url // Send back the processed image URL if needed for display
                ) );
            }
        } else {
            wp_send_json_error( array( 'message' => 'Error uploading file: ' . (isset($movefile['error']) ? $movefile['error'] : 'Unknown error') ) );
        }
        wp_die(); // this is required to terminate immediately and return a proper response
    }
}
