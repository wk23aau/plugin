<div class="wrap">
    <h1>ArchOpinion Settings &amp; Policies</h1>
    <p>Configure your Gemini API Key and manage architectural analysis policies.</p>

    <form method="post" action="options.php" id="archopinion-settings-form">
        <?php
        // This prints out all hidden setting fields
        settings_fields( ArchOpinion_Admin::SETTINGS_GROUP ); // Use the constant for the settings group name

        // This prints out all settings sections added to a particular settings page
        do_settings_sections( ArchOpinion_Admin::SETTINGS_GROUP ); // Use the constant

        // Nonce for saving settings (especially for policy add/remove if not using pure AJAX for everything)
        wp_nonce_field( 'archopinion_settings_save_nonce', '_wpnonce_archopinion_settings_save' );
        ?>
        <?php submit_button('Save Settings & New Policy'); ?>
    </form>

    <hr>
    <h2>Manage Existing Policies</h2>
    <p>Policies listed here are used by the Gemini model during analysis. You can remove them using the buttons below.</p>
    <div id="archopinion-existing-policies-container">
        <?php
        $policy_manager = new ArchOpinion_Policy_Manager();
        $policies = $policy_manager->get_policies();

        if ( ! empty( $policies ) ) : ?>
            <ul id="archopinion-policy-list" class="ul-disc">
                <?php foreach ( $policies as $policy_key => $policy_text ) : ?>
                    <li id="policy-item-<?php echo esc_attr( $policy_key ); ?>">
                        <strong><?php echo esc_html( ucwords( str_replace( '_', ' ', $policy_key ) ) ); ?>:</strong>
                        <p style="padding-left: 20px; font-style: italic;"><?php echo nl2br( esc_html( $policy_text ) ); ?></p>
                        <button class="button button-secondary button-small archopinion-remove-policy-button" data-policy-key="<?php echo esc_attr( $policy_key ); ?>">
                            Remove Policy
                        </button>
                        <span class="spinner" style="display: none; float: none; margin-top: 0;"></span>
                    </li>
                    <hr style="margin: 10px 0;">
                <?php endforeach; ?>
            </ul>
        <?php else : ?>
            <p id="archopinion-no-policies-message">No policies defined yet. Add one using the form above.</p>
        <?php endif; ?>
    </div>
    <div id="archopinion-policy-feedback" style="display:none; margin-top:10px;"></div>

</div>
<script type="text/template" id="archopinion-policy-item-template">
    <li id="policy-item-{{policy_key}}">
        <strong>{{policy_name_formatted}}:</strong>
        <p style="padding-left: 20px; font-style: italic;">{{policy_text_formatted}}</p>
        <button class="button button-secondary button-small archopinion-remove-policy-button" data-policy-key="{{policy_key}}">
            Remove Policy
        </button>
        <span class="spinner" style="display: none; float: none; margin-top: 0;"></span>
    </li>
    <hr style="margin: 10px 0;">
</script>
