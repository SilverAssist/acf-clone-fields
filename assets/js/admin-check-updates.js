/**
 * ACF Clone Fields - Check Updates JavaScript
 *
 * Handles the "Check Updates" button functionality in Settings Hub.
 * Called when user clicks the "Check Updates" button on the plugin card.
 *
 * @package SilverAssist\ACFCloneFields
 * @since 1.0.0
 */

/**
 * Main function to check for updates
 * This function is called by the Settings Hub action button
 */
function silverAssistACFCloneCheckUpdates() {
    // Check if jQuery and data are available
    if (typeof jQuery === 'undefined' || typeof silverAssistACFCloneCheckUpdatesData === 'undefined') {
        console.error('ACF Clone Fields: jQuery or update data not available');
        return false;
    }

    var $ = jQuery;
    var data = silverAssistACFCloneCheckUpdatesData;
    
    // Find the check updates button
    var checkUpdatesBtn = $('.silverassist-plugin-card[data-plugin="acf-clone-fields"] .button-primary');
    
    if (!checkUpdatesBtn.length) {
        console.error('ACF Clone Fields: Check updates button not found');
        return false;
    }

    // Store original button state
    var originalText = checkUpdatesBtn.text();
    var originalClass = checkUpdatesBtn.attr('class');
    
    // Update button to show checking state
    checkUpdatesBtn
        .text(data.strings.checking)
        .prop('disabled', true)
        .removeClass('button-primary')
        .addClass('button-secondary');

    // Perform AJAX call to check for updates
    $.post(data.ajaxurl, {
        action: 'silver_acf_clone_check_version',
        nonce: data.nonce
    })
    .done(function(response) {
        if (response.success) {
            if (response.data && response.data.update_available) {
                // Update available - redirect to updates page
                checkUpdatesBtn.text(data.strings.updateAvailable);
                setTimeout(function() {
                    window.location.href = data.updateUrl;
                }, 1500);
            } else {
                // Up to date
                checkUpdatesBtn.text(data.strings.upToDate);
                setTimeout(function() {
                    resetButton();
                }, 3000);
            }
        } else {
            // API error
            checkUpdatesBtn.text(data.strings.checkError);
            setTimeout(function() {
                resetButton();
            }, 3000);
        }
    })
    .fail(function(xhr, status, error) {
        // Connection error
        console.error('ACF Clone Fields update check failed:', status, error);
        checkUpdatesBtn.text(data.strings.connectError);
        setTimeout(function() {
            resetButton();
        }, 3000);
    });

    /**
     * Reset button to original state
     */
    function resetButton() {
        checkUpdatesBtn
            .text(originalText)
            .prop('disabled', false)
            .attr('class', originalClass);
    }

    return false;
}

/**
 * Initialize when document is ready
 */
jQuery(document).ready(function($) {
    // Make the function globally available
    window.silverAssistACFCloneCheckUpdates = silverAssistACFCloneCheckUpdates;
    
    // Log successful initialization
    console.log('ACF Clone Fields: Check updates functionality initialized');
});