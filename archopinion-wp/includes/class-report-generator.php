<?php

class ArchOpinion_Report_Generator {

    public function generate_report( $analysis_data, $image_path = null ) { // Added $image_path = null for consistency if not used
        // Ensure $analysis_data and its keys are set to avoid errors
        $request_data = isset($analysis_data['request_data']) && is_array($analysis_data['request_data']) ? $analysis_data['request_data'] : [];
        $analysis_result = isset($analysis_data['analysis_result']) && is_array($analysis_data['analysis_result']) ? $analysis_data['analysis_result'] : [];

        // Create a unique filename
        $filename = 'report_' . date('Ymd_His') . '_' . uniqid() . '.html';

        // Define the path to the custom reports directory
        $upload_dir = wp_upload_dir();
        $report_dir = $upload_dir['basedir'] . '/archopinion-reports/';

        // Create the directory if it doesn't exist
        if ( ! file_exists( $report_dir ) ) {
            wp_mkdir_p( $report_dir );
        }

        $filepath = $report_dir . $filename;

        // Start HTML content
        $html_content = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archopinion Analysis Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; color: #333; }
        h1, h2, h3 { color: #1a472a; }
        h1 { text-align: center; border-bottom: 2px solid #1a472a; padding-bottom: 10px; }
        h2 { border-bottom: 1px solid #eee; padding-bottom: 5px; margin-top: 30px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; vertical-align: top; }
        th { background-color: #f2f2f2; }
        .section { margin-bottom: 20px; padding: 15px; border: 1px solid #e0e0e0; border-radius: 5px; background-color: #f9f9f9;}
        .section h3 { margin-top: 0; color: #265c3b; }
        .status-compliant { color: green; font-weight: bold; }
        .status-partially-compliant { color: orange; font-weight: bold; }
        .status-non-compliant { color: red; font-weight: bold; }
        ul { padding-left: 20px; margin-top: 0; }
        dl { margin-bottom: 15px; }
        dt { font-weight: bold; color: #265c3b; }
        dd { margin-left: 0; margin-bottom: 10px; padding-left: 15px; border-left: 3px solid #eee; }
        footer { margin-top: 40px; text-align: center; font-size: 0.9em; color: #777; }
    </style>
</head>
<body>
    <h1>AI Architectural Review Report</h1>';

        // Project Information
        $html_content .= '<div class="section">
        <h2>Project Information</h2>';
        $html_content .= '<dl>';
        $html_content .= '<dt>Project Address:</dt><dd>' . (isset($request_data['project_address']) ? esc_html($request_data['project_address']) : 'N/A') . '</dd>';
        $html_content .= '<dt>Project Type:</dt><dd>' . (isset($request_data['project_type']) ? esc_html($request_data['project_type']) : 'N/A') . '</dd>';
        $html_content .= '<dt>Council:</dt><dd>' . (isset($request_data['council']) ? esc_html($request_data['council']) : 'N/A') . '</dd>';
        $html_content .= '<dt>Planning Reference:</dt><dd>' . (isset($request_data['planning_reference']) ? esc_html($request_data['planning_reference']) : 'N/A') . '</dd>';
        $html_content .= '<dt>Analysis Date:</dt><dd>' . esc_html(date('F j, Y, g:i a')) . '</dd>';
        $html_content .= '</dl>';
        $html_content .= '</div>';

        // Regulatory Framework Analysis
        $aiReviewFramework = isset($analysis_result['aiReviewFramework']) && is_array($analysis_result['aiReviewFramework']) ? $analysis_result['aiReviewFramework'] : [];
        if (!empty($aiReviewFramework)) {
            $html_content .= '<div class="section">
            <h2>Regulatory Framework Analysis</h2>';
            foreach ($aiReviewFramework as $framework) {
                $html_content .= '<h3>' . (isset($framework['framework_name']) ? esc_html($framework['framework_name']) : 'Unnamed Framework') . '</h3>';
                $html_content .= '<dl>';
                if (isset($framework['key_considerations']) && is_array($framework['key_considerations']) && !empty($framework['key_considerations'])) {
                    $html_content .= '<dt>Key Considerations:</dt><dd><ul>';
                    foreach ($framework['key_considerations'] as $consideration) {
                        $html_content .= '<li>' . esc_html($consideration) . '</li>';
                    }
                    $html_content .= '</ul></dd>';
                }
                if (isset($framework['relevant_policies']) && is_array($framework['relevant_policies']) && !empty($framework['relevant_policies'])) {
                    $html_content .= '<dt>Relevant Policies:</dt><dd><ul>';
                    foreach ($framework['relevant_policies'] as $policy) {
                        $html_content .= '<li>' . esc_html($policy) . '</li>';
                    }
                    $html_content .= '</ul></dd>';
                }
                $html_content .= '</dl>';
            }
            $html_content .= '</div>';
        }

        // Plan-by-Plan Review
        $planByPlanReview = isset($analysis_result['planByPlanReview']) && is_array($analysis_result['planByPlanReview']) ? $analysis_result['planByPlanReview'] : [];
        if (!empty($planByPlanReview)) {
            $html_content .= '<div class="section">
            <h2>Plan-by-Plan Review</h2>';
            foreach ($planByPlanReview as $plan) {
                $html_content .= '<h3>' . (isset($plan['plan_type']) ? esc_html($plan['plan_type']) : 'Unnamed Plan') . '</h3>';
                $html_content .= '<dl>';
                if (isset($plan['positives']) && !empty($plan['positives'])) {
                     $html_content .= '<dt>Positives:</dt><dd>';
                     if(is_array($plan['positives'])) {
                        $html_content .= '<ul>';
                        foreach($plan['positives'] as $positive) $html_content .= '<li>' . esc_html($positive) . '</li>';
                        $html_content .= '</ul>';
                     } else {
                        $html_content .= esc_html($plan['positives']);
                     }
                     $html_content .= '</dd>';
                }
                if (isset($plan['observations']) && !empty($plan['observations'])) {
                    $html_content .= '<dt>Observations:</dt><dd>';
                    if(is_array($plan['observations'])) {
                        $html_content .= '<ul>';
                        foreach($plan['observations'] as $observation) $html_content .= '<li>' . esc_html($observation) . '</li>';
                        $html_content .= '</ul>';
                     } else {
                        $html_content .= esc_html($plan['observations']);
                     }
                    $html_content .= '</dd>';
                }
                if (isset($plan['compliance_notes']) && !empty($plan['compliance_notes'])) {
                    $html_content .= '<dt>Compliance Notes:</dt><dd>' . esc_html($plan['compliance_notes']) . '</dd>';
                }
                $html_content .= '</dl>';
            }
            $html_content .= '</div>';
        }

        // Policy Compatibility Summary
        $policyCompatibilitySummary = isset($analysis_result['policyCompatibilitySummary']) && is_array($analysis_result['policyCompatibilitySummary']) ? $analysis_result['policyCompatibilitySummary'] : [];
        if (!empty($policyCompatibilitySummary)) {
            $html_content .= '<div class="section">
            <h2>Policy Compatibility Summary</h2>';
            $html_content .= '<table><thead><tr><th>Policy Area</th><th>Status</th><th>Details</th><th>Recommendations</th></tr></thead><tbody>';
            foreach ($policyCompatibilitySummary as $policy_item) {
                $status_class = '';
                if (isset($policy_item['status'])) {
                    if (strtolower($policy_item['status']) == 'compliant') $status_class = 'status-compliant';
                    elseif (strtolower($policy_item['status']) == 'partially compliant') $status_class = 'status-partially-compliant';
                    elseif (strtolower($policy_item['status']) == 'non-compliant') $status_class = 'status-non-compliant';
                }
                $html_content .= '<tr>';
                $html_content .= '<td>' . (isset($policy_item['policy_area']) ? esc_html($policy_item['policy_area']) : 'N/A') . '</td>';
                $html_content .= '<td class="' . esc_attr($status_class) . '">' . (isset($policy_item['status']) ? esc_html($policy_item['status']) : 'N/A') . '</td>';
                $html_content .= '<td>' . (isset($policy_item['details']) ? nl2br(esc_html($policy_item['details'])) : 'N/A') . '</td>';
                $html_content .= '<td>' . (isset($policy_item['recommendations']) ? nl2br(esc_html($policy_item['recommendations'])) : 'N/A') . '</td>';
                $html_content .= '</tr>';
            }
            $html_content .= '</tbody></table></div>';
        }

        // AI Recommendations Summary
        $aiRecommendationSummary = isset($analysis_result['aiRecommendationSummary']) ? $analysis_result['aiRecommendationSummary'] : '';
        if (!empty($aiRecommendationSummary)) {
            $html_content .= '<div class="section">
            <h2>AI Recommendations Summary</h2>';
            $html_content .= '<p>' . nl2br(esc_html($aiRecommendationSummary)) . '</p>';
            $html_content .= '</div>';
        }

        // Footer
        $html_content .= '<footer>
        <p>Disclaimer: This AI-generated report is for informational purposes only and should not be considered as professional architectural or planning advice. Always consult with qualified professionals and your local planning authority before proceeding with any development.</p>
    </footer>
</body>
</html>';

        // Save HTML content to file
        file_put_contents($filepath, $html_content);

        // Return the path and URL to the file
        return [
            'path' => $filepath,
            'url'  => $upload_dir['baseurl'] . '/archopinion-reports/' . $filename
        ];
    }
}
