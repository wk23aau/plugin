jQuery(document).ready(function($) {
    // New Analysis Form Submission
    $('#archopinion-new-analysis-form').on('submit', function(e) {
        e.preventDefault();

        var formData = new FormData(this);
        formData.append('action', 'archopinion_new_analysis');
        formData.append('nonce', archopinion_ajax.new_analysis_nonce); // Use localized nonce

        var $form = $(this);
        var $submitButton = $form.find('input[type="submit"]');
        var $spinner = $('#archopinion-analysis-spinner');
        var $resultsContainer = $('#archopinion-analysis-results-container');
        var $outputDiv = $('#archopinion-analysis-output');
        var $downloadLinkContainer = $('#archopinion-report-download-link-container');
        var $downloadLink = $('#archopinion-report-download-link');
        var $errorDiv = $('#archopinion-error-message');

        $submitButton.prop('disabled', true);
        $spinner.show();
        $resultsContainer.hide();
        $outputDiv.html('');
        $downloadLinkContainer.hide();
        $errorDiv.hide().html('');


        $.ajax({
            url: archopinion_ajax.ajax_url, // Use localized ajax_url
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                if (response.success) {
                    $outputDiv.html(response.data.analysis.replace(/\n/g, '<br>')); // Display analysis
                    if(response.data.report_url) {
                        $downloadLink.attr('href', response.data.report_url);
                        $downloadLink.attr('target', '_blank'); // Open in new tab
                        $downloadLink.text('View Report'); // Change button text
                        $downloadLinkContainer.show();
                    }
                    $resultsContainer.show();
                } else {
                    $errorDiv.html('<strong>Error:</strong> ' + response.data.message).show();
                }
            },
            error: function(xhr, status, error) {
                var errorMessage = xhr.responseText ? xhr.responseText : ('AJAX error: ' + status + ' - ' + error);
                try {
                    var errorObj = JSON.parse(xhr.responseText);
                    if (errorObj && errorObj.data && errorObj.data.message) {
                        errorMessage = errorObj.data.message;
                    } else if (errorObj && errorObj.message) {
                         errorMessage = errorObj.message;
                    }
                } catch (e) {
                    // console.error("Could not parse error response:", e);
                    // Use default error message if parsing fails
                }
                $errorDiv.html('<strong>Error:</strong> ' + errorMessage).show();
            },
            complete: function() {
                $submitButton.prop('disabled', false);
                $spinner.hide();
            }
        });
    });

    // Image preview for New Analysis
    $('#analysis_image').on('change', function(event) {
        var reader = new FileReader();
        reader.onload = function(e) {
            $('#archopinion-image-preview').attr('src', e.target.result).show();
        }
        if (event.target.files[0]) {
            reader.readAsDataURL(event.target.files[0]);
        } else {
             $('#archopinion-image-preview').attr('src', '#').hide();
        }
    });


    // Settings Page - Remove Policy
    $('#archopinion-existing-policies-container').on('click', '.archopinion-remove-policy-button', function() {
        var $button = $(this);
        var policyKey = $button.data('policy-key');
        var $listItem = $('#policy-item-' + policyKey);
        var $spinner = $listItem.find('.spinner');
        var $feedbackDiv = $('#archopinion-policy-feedback');

        if (!policyKey) {
            console.error('Policy key not found for removal.');
            return;
        }

        if (!confirm('Are you sure you want to remove this policy: ' + policyKey.replace(/_/g, ' ') + '?')) {
            return;
        }

        $button.prop('disabled', true);
        $spinner.css('display', 'inline-block'); // Show spinner
        $feedbackDiv.hide().removeClass('notice-success notice-error').html('');


        // Instead of direct AJAX, we'll submit the main form with an indicator of what to remove.
        // This is a non-AJAX fallback approach for policy removal.
        // For true AJAX removal, you would need a separate AJAX handler.
        // For simplicity with the current PHP structure for saving settings:
        // We add a hidden input to the main settings form and submit it.
        // The PHP `sanitize_policies` callback in class-admin.php needs to handle this.

        // Find the main settings form
        var $settingsForm = $('#archopinion-settings-form');
        if ($settingsForm.length === 0) {
            console.error("Settings form not found.");
            $button.prop('disabled', false);
            $spinner.hide();
            return;
        }

        // Remove any existing hidden input for removal to avoid conflicts
        $settingsForm.find('input[name="remove_this_policy_key"]').remove();

        // Add a hidden input to signify which policy to remove
        $('<input>').attr({
            type: 'hidden',
            name: 'remove_this_policy_key', // The sanitize_policies method will look for this
            value: policyKey
        }).appendTo($settingsForm);

         // Add a hidden input for the button that was clicked, so the PHP knows which action to take.
        $('<input>').attr({
            type: 'hidden',
            name: 'remove_policy_button', // This will be checked in PHP
            value: policyKey // The value could be the policy key itself
        }).appendTo($settingsForm);


        // Submit the main settings form
        // This will trigger the 'Save Settings' flow, and our sanitize_policies will catch the removal.
        $settingsForm.submit();

        // Note: The page will reload after form submission.
        // For a pure AJAX experience without page reload, you'd need a dedicated AJAX action
        // and handler for removing policies, which would then update the list dynamically on success.
        // The current `sanitize_policies` can act as that handler if it checks for an AJAX request.
        // For now, this simulates removal via the main settings save process.
    });


    // Handle the settings form submission for adding new policies (if JS is active)
    // This part is a bit tricky because `options.php` handles the actual saving.
    // We are mostly using the built-in WordPress settings API.
    // The main `submit_button()` in `settings.php` will trigger the form submission to `options.php`.
    // The `sanitize_policies` function in `class-admin.php` handles the logic for adding
    // the new policy based on `$_POST` values.

    // What we can do here is provide some client-side feedback or validation if needed,
    // but the actual addition happens on the server side when the form is submitted.

    // Example: Clear new policy fields after form submission success (if the page didn't reload)
    // This is more relevant if we were doing a full AJAX save of settings, which we are not for the main settings.
    // $('#archopinion-settings-form').on('submit', function(e) {
        // This is generally handled by page reload. If settings are saved via AJAX in the future,
        // this is where you'd clear the fields and update the policy list.
        // For now, the server-side redirect or page reload after saving settings will clear them.
    // });


    // Update policy list dynamically if a new policy was added (after settings save)
    // This requires passing some data back from the server if the save was successful,
    // or re-fetching the list. WordPress typically reloads the page.
    // If `window.location.search` includes `settings-updated=true`, it means settings were saved.
    if (window.location.search.includes('settings-updated=true')) {
        var $noPoliciesMessage = $('#archopinion-no-policies-message');
        var $policyList = $('#archopinion-policy-list');
        var $feedbackDiv = $('#archopinion-policy-feedback');

        // Check if the "Add New Policy" fields were cleared by the server-side logic (they should be)
        var newPolicyName = $('#archopinion_new_policy_name_id').val();
        var newPolicyText = $('#archopinion_new_policy_text_id').val();

        if (newPolicyName === '' && newPolicyText === '') {
            // This implies a new policy might have been successfully added and fields cleared.
            // The page reloads, so the list should be up-to-date from PHP.
            // We can show a generic success message for adding.
            // This is a bit of a guess, as we don't know *which* policy was added without more info from server.
            // The `sanitize_policies` function would need to set a transient or something to confirm.

            // For a truly dynamic update without page reload (full AJAX save), you'd get the new policy data
            // in an AJAX success callback and use the template below to append it.
        }

        // If a policy was removed, the page also reloads, and the list is updated by PHP.
    }


    // Template for adding a new policy to the list (if doing it via AJAX in the future)
    /*
    function addPolicyToList(policyKey, policyName, policyText) {
        var $noPoliciesMessage = $('#archopinion-no-policies-message');
        var $policyList = $('#archopinion-policy-list');
        var $template = $('#archopinion-policy-item-template').html();

        if ($noPoliciesMessage.length) {
            $noPoliciesMessage.hide();
        }

        var policyNameFormatted = policyName.replace(/_/g, ' ').replace(/\b\w/g, function(l){ return l.toUpperCase(); });
        var policyTextFormatted = policyText.replace(/\n/g, '<br>'); // Basic formatting

        var newItem = $template
            .replace(/{{policy_key}}/g, policyKey)
            .replace(/{{policy_name_formatted}}/g, policyNameFormatted)
            .replace(/{{policy_text_formatted}}/g, policyTextFormatted);

        if ($policyList.length === 0) {
            // Create the list if it doesn't exist
            $('#archopinion-existing-policies-container').append('<ul id="archopinion-policy-list" class="ul-disc"></ul>');
            $policyList = $('#archopinion-policy-list');
        }
        $policyList.append(newItem);
    }
    */

});
