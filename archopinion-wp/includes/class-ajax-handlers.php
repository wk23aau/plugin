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
                // Prepare data for the report generator
                // Assuming $analysis_result from Gemini client IS the structured data needed for the report's 'analysis_result' key.
                // And request_data needs to be assembled from POST or other sources if available.
                // For now, let's simulate some request_data. In a real scenario, this would come from the form submission.
                $request_data_for_report = [
                    'project_address' => isset($_POST['project_address']) ? sanitize_text_field($_POST['project_address']) : 'N/A',
                    'project_type' => isset($_POST['project_type']) ? sanitize_text_field($_POST['project_type']) : 'N/A',
                    'council' => isset($_POST['council']) ? sanitize_text_field($_POST['council']) : 'N/A',
                    'planning_reference' => isset($_POST['planning_reference']) ? sanitize_text_field($_POST['planning_reference']) : 'N/A',
                ];

                $report_input_data = [
                    'request_data' => $request_data_for_report,
                    'analysis_result' => $analysis_result // Directly passing the Gemini output.
                                                          // This assumes $analysis_result is already the structured array.
                                                          // If $analysis_result['analysis'] is the text blob and we need to parse it,
                                                          // this part would need adjustment, or the Gemini client would.
                ];

                // Generate HTML report
                $report_generator = new ArchOpinion_Report_Generator();
                // The first argument to generate_report is $analysis_data which includes 'request_data' and 'analysis_result'
                // The second argument is $image_path
                $report_info = $report_generator->generate_report( $report_input_data, $image_path );

                if ( is_wp_error( $report_info ) ) {
                    wp_delete_file( $image_path ); // Clean up
                    wp_send_json_error( array( 'message' => 'Failed to generate HTML report: ' . $report_info->get_error_message() ) );
                    return;
                }

                // $report_info already contains 'path' and 'url'. File is already saved by generate_report.

                wp_delete_file( $image_path ); // Clean up the original uploaded image after successful report generation

                // If the Gemini client returns $analysis_result['analysis'] as the main text output
                // and $analysis_result itself is the structured data.
                // We need to decide what to send back in 'analysis' for direct display if anything.
                // For now, let's assume the main text for quick display is $analysis_result['aiRecommendationSummary'] or similar.
                // Or, if $analysis_result from Gemini is *just* text, then that's $analysis_result['analysis'].
                // This part needs clarification based on actual Gemini client output vs generate_report input.
                // Let's assume $analysis_result (from Gemini) is the structured data.
                // And for the 'analysis' field in JSON response (quick preview), we might use a summary.
                $display_analysis_summary = isset($analysis_result['aiRecommendationSummary']) ? $analysis_result['aiRecommendationSummary'] : 'See full report for details.';
                if (is_array($analysis_result) && isset($analysis_result['analysis']) && is_string($analysis_result['analysis']) && empty($display_analysis_summary)) {
                    // Fallback if the above assumption is wrong and old structure of $analysis_result['analysis'] (text blob) exists
                     $display_analysis_summary = $analysis_result['analysis'];
                }


                wp_send_json_success( array(
                    'message' => 'Analysis complete.',
                    'analysis' => $display_analysis_summary, // Send a summary or relevant part for immediate display
                    'report_url' => $report_info['url'],    // URL to the new HTML report
                    'image_url' => $image_url
                ) );
            }
        } else {
            wp_send_json_error( array( 'message' => 'Error uploading file: ' . (isset($movefile['error']) ? $movefile['error'] : 'Unknown error') ) );
        }
        wp_die(); // this is required to terminate immediately and return a proper response
    }
}
