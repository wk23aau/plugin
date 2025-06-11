<div class="wrap">
    <h1>New Architectural Analysis</h1>
    <p>Upload an architectural design image (e.g., floor plan, elevation) to get an analysis from Gemini.</p>

    <form id="archopinion-new-analysis-form" method="post" enctype="multipart/form-data">
        <?php // wp_nonce_field( 'archopinion_new_analysis_nonce', '_wpnonce_archopinion_new_analysis' ); // Nonce is handled in JS ?>

        <table class="form-table">
            <tr valign="top">
                <th scope="row">
                    <label for="analysis_image">Upload Design Image</label>
                </th>
                <td>
                    <input type="file" id="analysis_image" name="analysis_image" class="regular-text" accept="image/*" required>
                    <p class="description">Supported formats: JPG, PNG, GIF. Max size: <?php echo size_format( wp_max_upload_size() ); ?>.</p>
                    <div id="archopinion-image-preview-container" style="margin-top:10px;">
                        <img id="archopinion-image-preview" src="#" alt="Image Preview" style="max-width:300px; max-height:300px; display:none;" />
                    </div>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">
                    <label for="custom_prompt">Custom Prompt (Optional)</label>
                </th>
                <td>
                    <textarea id="custom_prompt" name="custom_prompt" rows="5" class="large-text" placeholder="Enter any specific questions or areas of focus for the analysis. This will be combined with any active policies."></textarea>
                    <p class="description">For example: "Focus on the passive cooling strategies." or "Are there any potential accessibility issues in the main entrance?"</p>
                </td>
            </tr>
        </table>

        <?php submit_button( 'Start Analysis', 'primary', 'submit_new_analysis' ); ?>
    </form>

    <div id="archopinion-analysis-spinner" style="display:none; margin-top:20px; text-align:center;">
        <img src="<?php echo esc_url( admin_url( 'images/wpspin_light.gif' ) ); ?>" alt="Loading..." />
        <p>Analyzing, please wait...</p>
    </div>

    <div id="archopinion-analysis-results-container" style="margin-top:20px; display:none;">
        <h2>Analysis Results</h2>
        <div id="archopinion-analysis-output" style="background-color: #f9f9f9; border: 1px solid #ccc; padding: 15px; border-radius: 4px; white-space: pre-wrap; font-family: monospace;">
            <!-- Analysis text will be inserted here -->
        </div>
        <p id="archopinion-report-download-link-container" style="margin-top:15px; display:none;">
            <a href="#" id="archopinion-report-download-link" class="button button-secondary" download>Download Full Report (PDF)</a>
        </p>
    </div>
     <div id="archopinion-error-message" style="display:none; margin-top:20px; padding:10px; border:1px solid red; background-color:#ffe0e0; color:red;">
        <!-- Error messages will be shown here -->
    </div>
</div>
<script>
    // Basic preview script, can be moved to admin.js if preferred and if it becomes more complex
    document.addEventListener('DOMContentLoaded', function() {
        const imageInput = document.getElementById('analysis_image');
        const imagePreview = document.getElementById('archopinion-image-preview');
        const imagePreviewContainer = document.getElementById('archopinion-image-preview-container');

        if (imageInput && imagePreview && imagePreviewContainer) {
            imageInput.addEventListener('change', function(event) {
                if (event.target.files && event.target.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        imagePreview.src = e.target.result;
                        imagePreview.style.display = 'block';
                        imagePreviewContainer.style.display = 'block'; // Ensure container is visible
                    }
                    reader.readAsDataURL(event.target.files[0]);
                } else {
                    imagePreview.src = '#';
                    imagePreview.style.display = 'none';
                   // imagePreviewContainer.style.display = 'none'; // Hide container if no file
                }
            });
        }
    });
</script>
