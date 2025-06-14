/**
 * Amazon Affiliate Pro - Admin Scripts
 *
 * @package AAPI
 * @since   1.0.0
 */

(function($) {
    'use strict';

    // Main admin object
    window.AAPI_Admin = {
        
        /**
         * Initialize admin scripts
         */
        init: function() {
            this.bindEvents();
            this.initColorPicker();
            this.initConditionalFields();
            this.initTabs();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Test API connection
            $('#aapi-test-api').on('click', this.testApiConnection);
            
            // Refresh product data
            $('#aapi-refresh-product').on('click', this.refreshProduct);
            
            // Settings form
            $('.aapi-settings-form').on('submit', this.validateSettings);
            
            // Conditional fields
            $('input[type="checkbox"], select').on('change', this.toggleConditionalFields);
            
            // Import form
            $('#aapi-import-form').on('submit', this.handleImport);
            
            // Bulk import
            $('#aapi-bulk-import').on('click', this.handleBulkImport);
            
            // Tab navigation
            $('.aapi-tab').on('click', this.switchTab);
        },

        /**
         * Initialize color picker
         */
        initColorPicker: function() {
            if ($.fn.wpColorPicker) {
                $('.aapi-color-picker').wpColorPicker();
            }
        },

        /**
         * Initialize conditional fields
         */
        initConditionalFields: function() {
            this.toggleConditionalFields();
        },

        /**
         * Initialize tabs
         */
        initTabs: function() {
            $('.aapi-tabs').each(function() {
                $(this).find('.aapi-tab:first').addClass('active');
                $(this).siblings('.aapi-tab-content:first').addClass('active');
            });
        },

        /**
         * Test API connection
         */
        testApiConnection: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $spinner = $button.next('.spinner');
            var $results = $('#aapi-test-results');
            
            $button.prop('disabled', true);
            $spinner.addClass('is-active');
            $results.hide().removeClass('success error');
            
            $.ajax({
                url: aapi_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'aapi_test_api',
                    nonce: aapi_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $results.addClass('success').html(
                            '<strong>' + response.message + '</strong><br>' +
                            (response.details ? JSON.stringify(response.details, null, 2) : '')
                        ).show();
                    } else {
                        $results.addClass('error').html(
                            '<strong>' + response.message + '</strong><br>' +
                            (response.error || aapi_admin.strings.error)
                        ).show();
                    }
                },
                error: function() {
                    $results.addClass('error').text(aapi_admin.strings.error).show();
                },
                complete: function() {
                    $button.prop('disabled', false);
                    $spinner.removeClass('is-active');
                }
            });
        },

        /**
         * Refresh product data
         */
        refreshProduct: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $spinner = $button.next('.spinner');
            var asin = $button.data('asin');
            
            if (!asin) {
                alert('ASIN not found');
                return;
            }
            
            $button.prop('disabled', true);
            $spinner.addClass('is-active');
            
            $.ajax({
                url: aapi_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'aapi_refresh_product',
                    asin: asin,
                    post_id: $('#post_ID').val(),
                    nonce: aapi_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert(aapi_admin.strings.success);
                        location.reload();
                    } else {
                        alert(response.message || aapi_admin.strings.error);
                    }
                },
                error: function() {
                    alert(aapi_admin.strings.error);
                },
                complete: function() {
                    $button.prop('disabled', false);
                    $spinner.removeClass('is-active');
                }
            });
        },

        /**
         * Validate settings form
         */
        validateSettings: function(e) {
            var $form = $(this);
            var valid = true;
            
            // Validate required fields
            $form.find('input[required], select[required]').each(function() {
                if (!$(this).val()) {
                    $(this).addClass('error');
                    valid = false;
                } else {
                    $(this).removeClass('error');
                }
            });
            
            if (!valid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        },

        /**
         * Toggle conditional fields
         */
        toggleConditionalFields: function() {
            $('[data-condition]').each(function() {
                var $field = $(this);
                var condition = $field.data('condition');
                var parts = condition.split('|');
                
                if (parts.length !== 3) return;
                
                var targetField = parts[0];
                var operator = parts[1];
                var value = parts[2];
                
                var $target = $('[name*="[' + targetField + ']"]');
                var targetValue = $target.is(':checkbox') ? $target.is(':checked') : $target.val();
                
                var show = false;
                
                switch (operator) {
                    case '==':
                        show = targetValue == value;
                        break;
                    case '!=':
                        show = targetValue != value;
                        break;
                }
                
                if (show) {
                    $field.closest('tr').show();
                } else {
                    $field.closest('tr').hide();
                }
            });
        },

        /**
         * Handle product import
         */
        handleImport: function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $button = $form.find('button[type="submit"]');
            var $spinner = $button.next('.spinner');
            var $results = $('#aapi-import-results');
            
            $button.prop('disabled', true).text(aapi_admin.strings.processing);
            $spinner.addClass('is-active');
            
            $.ajax({
                url: aapi_admin.ajax_url,
                type: 'POST',
                data: $form.serialize() + '&action=aapi_import_product&nonce=' + aapi_admin.nonce,
                success: function(response) {
                    if (response.success) {
                        $results.html(response.html).addClass('active');
                        
                        if (response.redirect) {
                            setTimeout(function() {
                                window.location.href = response.redirect;
                            }, 2000);
                        }
                    } else {
                        alert(response.message || aapi_admin.strings.error);
                    }
                },
                error: function() {
                    alert(aapi_admin.strings.error);
                },
                complete: function() {
                    $button.prop('disabled', false).text($button.data('original-text') || 'Import');
                    $spinner.removeClass('is-active');
                }
            });
        },

        /**
         * Handle bulk import
         */
        handleBulkImport: function(e) {
            e.preventDefault();
            
            var asins = $('#aapi-bulk-asins').val();
            if (!asins) {
                alert('Please enter at least one ASIN');
                return;
            }
            
            var $button = $(this);
            var $progress = $('#aapi-bulk-progress');
            var asinList = asins.split('\n').filter(function(asin) {
                return asin.trim().length > 0;
            });
            
            $button.prop('disabled', true);
            $progress.show();
            
            // Process ASINs one by one
            var processed = 0;
            var errors = [];
            
            function processNext() {
                if (processed >= asinList.length) {
                    // All done
                    $button.prop('disabled', false);
                    alert('Import completed! Processed: ' + processed + ', Errors: ' + errors.length);
                    
                    if (errors.length > 0) {
                        console.log('Import errors:', errors);
                    }
                    
                    return;
                }
                
                var asin = asinList[processed].trim();
                $progress.find('.current').text(asin);
                $progress.find('.progress').text((processed + 1) + ' / ' + asinList.length);
                
                $.ajax({
                    url: aapi_admin.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'aapi_import_product',
                        asin: asin,
                        nonce: aapi_admin.nonce
                    },
                    success: function(response) {
                        if (!response.success) {
                            errors.push({asin: asin, error: response.message});
                        }
                    },
                    error: function() {
                        errors.push({asin: asin, error: 'Network error'});
                    },
                    complete: function() {
                        processed++;
                        setTimeout(processNext, 1000); // 1 second delay between imports
                    }
                });
            }
            
            processNext();
        },

        /**
         * Switch tab
         */
        switchTab: function(e) {
            e.preventDefault();
            
            var $tab = $(this);
            var target = $tab.data('tab');
            
            // Update active tab
            $tab.addClass('active').siblings().removeClass('active');
            
            // Show corresponding content
            $('#' + target).addClass('active').siblings('.aapi-tab-content').removeClass('active');
        },

        /**
         * Utility: Format number
         */
        formatNumber: function(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        },

        /**
         * Utility: Show notification
         */
        showNotification: function(message, type) {
            var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            $('.wrap > h1').after($notice);
            
            // Auto dismiss after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        AAPI_Admin.init();
    });

})(jQuery);