/**
 * WVP Checkout JavaScript
 * Handles VIP code verification and form autofill functionality
 */

console.log('WVP DEBUG: wvp-checkout.js loaded successfully');

jQuery(document).ready(function($) {
    'use strict';
    
    console.log('WVP DEBUG: Document ready fired');
    console.log('WVP DEBUG: jQuery version:', $.fn.jquery);
    console.log('WVP DEBUG: $ is:', $);
    console.log('WVP DEBUG: wvp_public_ajax available?', typeof wvp_public_ajax !== 'undefined');
    if (typeof wvp_public_ajax !== 'undefined') {
        console.log('WVP DEBUG: AJAX URL:', wvp_public_ajax.ajax_url);
        console.log('WVP DEBUG: Nonce:', wvp_public_ajax.nonce);
    }

    const WVP_Checkout = {
        
        // Initialize checkout functionality
        init: function() {
            console.log('WVP DEBUG: WVP_Checkout.init() called');
            this.vipCodeVerified = localStorage.getItem('wvp_vip_verified') === 'true';
            
            this.checkForSessionAutofill();
            
            this.initVipCodeSection();
            this.initCheckoutUpdates();
            this.initFormValidation();
            this.initPriceUpdates();
            this.initOrderReview();
        },

        // Check for autofill data from session after page reload
        checkForSessionAutofill: function() {
            if (typeof wvp_public_ajax !== 'undefined') {
                $.ajax({
                    url: wvp_public_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wvp_get_session_autofill',
                        nonce: wvp_public_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success && response.data.autofill_data) {
                            setTimeout(function() {
                                WVP_Checkout.autofillBillingForm(response.data.autofill_data);
                                WVP_Checkout.showMessage($('#wvp_code_messages'), 'success', response.data.message);
                            }, 1000);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('Session autofill check failed:', error);
                    }
                });
            }
        },

        // VIP code section functionality
        initVipCodeSection: function() {
            const $section = $('#wvp-checkout-vip-section');
            const $input = $('#wvp_code');
            const $button = $('#wvp_verify_code');
            const $messages = $('#wvp_code_messages');

            if (!$section.length) {
                return;
            }

            // Real-time code validation
            $input.on('input', this.validateCodeInput.bind(this));
            
            // Verify button click
            $button.on('click', this.verifyVipCode.bind(this));
            
            // Enter key support
            $input.on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    $button.click();
                }
            });

            // Initialize intersection observer for scroll-based interactions
            this.initScrollObserver();
        },

        // Initialize scroll observer for better UX
        initScrollObserver: function() {
            if (typeof IntersectionObserver === 'undefined') {
                return;
            }

            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        // Add focus when VIP section comes into view
                        setTimeout(function() {
                            const $input = $('#wvp_code');
                            if ($input.length && !$input.val() && !$input.is(':focus')) {
                                $input.focus();
                            }
                        }, 500);
                    }
                });
            }, { threshold: 0.5 });

            const section = document.getElementById('wvp-checkout-vip-section');
            if (section) {
                observer.observe(section);
            }
        },

        // Verify VIP code via AJAX
        verifyVipCode: function(e) {
            e.preventDefault();
            
            const $button = $(e.target);
            const $input = $('#wvp_code');
            const $messages = $('#wvp_code_messages');
            const code = $input.val().trim();

            if (!code) {
                this.showMessage($messages, 'error', 'Molimo unesite VIP kod');
                return;
            }

            // Collect billing data to send with verification
            const billingData = this.collectBillingData();

            // Show loading state
            this.setLoadingState($button, true);
            this.showMessage($messages, 'loading', 'Proverava VIP kod...');

            // AJAX call
            $.ajax({
                url: wvp_public_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'wvp_verify_code',
                    code: code,
                    billing_data: billingData,
                    nonce: wvp_public_ajax.nonce
                },
                success: function(response) {
                    this.handleVerificationSuccess(response);
                }.bind(this),
                error: function(xhr, status, error) {
                    this.handleVerificationError(xhr, status, error);
                }.bind(this),
                complete: function() {
                    this.setLoadingState($button, false);
                }.bind(this)
            });
        },

        // Handle successful verification
        handleVerificationSuccess: function(response) {
            const $messages = $('#wvp_code_messages');
            const $section = $('#wvp-checkout-vip-section');

            if (response.success) {
                // Check if this is a used code that needs email confirmation
                if (response.data && response.data.used_code) {
                    this.showEmailConfirmationPopup(response.data);
                    return;
                } else if (response.data && response.data.needs_registration) {
                    this.showRegistrationForm(response.data);
                    return;
                } else {
                    this.showMessage($messages, 'success', response.data.message);
                    
                    // Mark as verified
                    this.vipCodeVerified = true;
                    localStorage.setItem('wvp_vip_verified', 'true');
                    
                    // Disable input and button
                    $('#wvp_code').prop('disabled', true);
                    $('#wvp_verify_code').prop('disabled', true);
                    
                    // Hide section after delay
                    setTimeout(function() {
                        $section.slideUp('slow');
                    }, 3000);
                }
                
                // Refresh checkout to apply VIP pricing
                if (typeof $('body').trigger === 'function') {
                    $('body').trigger('update_checkout');
                }
            } else {
                this.showMessage($messages, 'error', response.data.message || 'Greška prilikom provere koda');
            }
        },

        // Handle verification error
        handleVerificationError: function() {
            const $messages = $('#wvp_code_messages');
            this.showMessage($messages, 'error', 'Greška prilikom AJAX poziva');
        },

        // Validate code input format
        validateCodeInput: function(e) {
            const $input = $(e.target);
            let value = $input.val().toUpperCase();
            
            // Remove invalid characters but keep letters, numbers and existing dashes
            value = value.replace(/[^A-Z0-9\-]/g, '');
            
            $input.val(value);
            
            // Enable/disable verify button based on content
            const cleanValue = value.replace(/\-/g, '');
            $('#wvp_verify_code').prop('disabled', cleanValue.length < 3);
            
            // Clear previous messages
            $('#wvp_code_messages').empty();
        },

        // Collect billing data from checkout form
        collectBillingData: function() {
            const billingData = {};
            const billingFields = [
                'billing_first_name', 'billing_last_name', 'billing_email', 'billing_phone',
                'billing_company', 'billing_address_1', 'billing_address_2', 'billing_city',
                'billing_state', 'billing_postcode', 'billing_country'
            ];

            billingFields.forEach(function(field) {
                const $field = $('#' + field);
                if ($field.length) {
                    billingData[field] = $field.val();
                }
            });

            return billingData;
        },

        // Show registration form for codes that need user data
        showRegistrationForm: function(data) {
            const codeData = data.code_data;
            
            const formHtml = `
                <div id="wvp-registration-form" class="wvp-modal-overlay">
                    <div class="wvp-modal-content">
                        <div class="wvp-modal-header">
                            <h3>Aktiviraj VIP članstvo</h3>
                            <button type="button" class="wvp-modal-close">&times;</button>
                        </div>
                        <div class="wvp-modal-body">
                            <p>Da biste aktivirali VIP kod <strong>${codeData.code}</strong>, potrebno je da unesete vaše podatke:</p>
                            
                            <form id="wvp-registration-form-inner">
                                <div class="form-row">
                                    <label for="wvp_reg_first_name">Ime *</label>
                                    <input type="text" id="wvp_reg_first_name" name="first_name" value="${codeData.first_name || ''}" required>
                                </div>
                                <div class="form-row">
                                    <label for="wvp_reg_last_name">Prezime *</label>
                                    <input type="text" id="wvp_reg_last_name" name="last_name" value="${codeData.last_name || ''}" required>
                                </div>
                                <div class="form-row">
                                    <label for="wvp_reg_email">Email *</label>
                                    <input type="email" id="wvp_reg_email" name="email" value="${codeData.email || ''}" required>
                                </div>
                                <div class="form-row">
                                    <label for="wvp_reg_phone">Telefon</label>
                                    <input type="text" id="wvp_reg_phone" name="phone" value="${codeData.phone || ''}">
                                </div>
                                <div class="form-row">
                                    <label for="wvp_reg_company">Kompanija</label>
                                    <input type="text" id="wvp_reg_company" name="company" value="${codeData.company || ''}">
                                </div>
                                <div class="form-row">
                                    <label for="wvp_reg_address_1">Adresa *</label>
                                    <input type="text" id="wvp_reg_address_1" name="address_1" value="${codeData.address_1 || ''}" required>
                                </div>
                                <div class="form-row">
                                    <label for="wvp_reg_city">Grad *</label>
                                    <input type="text" id="wvp_reg_city" name="city" value="${codeData.city || ''}" required>
                                </div>
                                <div class="form-row">
                                    <label for="wvp_reg_postcode">Poštanski broj *</label>
                                    <input type="text" id="wvp_reg_postcode" name="postcode" value="${codeData.postcode || ''}" required>
                                </div>
                                <div class="form-row">
                                    <label for="wvp_reg_country">Zemlja</label>
                                    <select id="wvp_reg_country" name="country">
                                        <option value="RS" ${(codeData.country || 'RS') === 'RS' ? 'selected' : ''}>Srbija</option>
                                        <option value="BA" ${codeData.country === 'BA' ? 'selected' : ''}>Bosna i Hercegovina</option>
                                        <option value="HR" ${codeData.country === 'HR' ? 'selected' : ''}>Hrvatska</option>
                                        <option value="ME" ${codeData.country === 'ME' ? 'selected' : ''}>Crna Gora</option>
                                    </select>
                                </div>
                                
                                <div class="wvp-modal-buttons">
                                    <button type="button" class="wvp-modal-close button">Otkaži</button>
                                    <button type="submit" class="button-primary">Aktiviraj VIP članstvo</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(formHtml);
            $('#wvp-registration-form').hide().fadeIn('slow');
            
            // Handle form submission
            $('#wvp-registration-form-inner').on('submit', function(e) {
                e.preventDefault();
                WVP_Checkout.submitRegistrationForm(codeData.id);
            });
            
            // Handle close buttons
            $('#wvp-registration-form .wvp-modal-close').on('click', function() {
                $('#wvp-registration-form').fadeOut('slow', function() {
                    $(this).remove();
                });
            });
        },

        // Submit registration form
        submitRegistrationForm: function(codeId) {
            const formData = {
                code_id: codeId,
                first_name: $('#wvp_reg_first_name').val(),
                last_name: $('#wvp_reg_last_name').val(),
                email: $('#wvp_reg_email').val(),
                phone: $('#wvp_reg_phone').val(),
                company: $('#wvp_reg_company').val(),
                address_1: $('#wvp_reg_address_1').val(),
                address_2: $('#wvp_reg_address_2').val(),
                city: $('#wvp_reg_city').val(),
                state: $('#wvp_reg_state').val(),
                postcode: $('#wvp_reg_postcode').val(),
                country: $('#wvp_reg_country').val()
            };

            $.ajax({
                url: wvp_public_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'wvp_register_and_activate',
                    user_data: formData,
                    nonce: wvp_public_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#wvp-registration-form').fadeOut('slow', function() {
                            $(this).remove();
                        });
                        
                        WVP_Checkout.showMessage($('#wvp_code_messages'), 'success', response.data.message);
                        
                        // Auto-fill checkout form
                        if (response.data.autofill_data) {
                            setTimeout(function() {
                                WVP_Checkout.autofillBillingForm(response.data.autofill_data);
                            }, 1000);
                        }
                        
                        // Hide VIP section
                        $('#wvp-checkout-vip-section').slideUp();
                        
                        // Refresh checkout
                        $('body').trigger('update_checkout');
                        
                    } else {
                        alert(response.data.message || 'Greška prilikom registracije');
                    }
                },
                error: function() {
                    alert('Greška prilikom AJAX poziva');
                }
            });
        },

        // Utility functions
        setLoadingState: function($button, loading) {
            if (loading) {
                $button.prop('disabled', true)
                       .find('.wvp-spinner').remove().end()
                       .prepend('<span class="wvp-spinner">⏳</span> ');
            } else {
                $button.prop('disabled', false)
                       .find('.wvp-spinner').remove();
            }
        },

        showMessage: function($container, type, message) {
            const messageHtml = `<div class="wvp-message ${type}">${message}</div>`;
            $container.html(messageHtml);
            
            if (type === 'success') {
                setTimeout(function() {
                    $container.find('.wvp-message.success').fadeOut();
                }, 4000);
            }
        },

        // Auto-fill billing form with VIP code data
        autofillBillingForm: function(data) {
            console.log('Autofilling billing form with data:', data);
            
            const fieldMappings = {
                'billing_first_name': data.billing_first_name,
                'billing_last_name': data.billing_last_name,
                'billing_email': data.billing_email,
                'billing_phone': data.billing_phone,
                'billing_company': data.billing_company,
                'billing_address_1': data.billing_address_1,
                'billing_address_2': data.billing_address_2,
                'billing_city': data.billing_city,
                'billing_state': data.billing_state,
                'billing_postcode': data.billing_postcode,
                'billing_country': data.billing_country
            };

            Object.entries(fieldMappings).forEach(function([fieldName, value]) {
                if (value) {
                    // Try multiple selectors to find the field
                    let $field = $('#' + fieldName);
                    
                    if (!$field.length) {
                        $field = $('input[name="' + fieldName + '"]');
                    }
                    
                    if (!$field.length) {
                        $field = $('select[name="' + fieldName + '"]');
                    }

                    if ($field.length) {
                        console.log('Filling field:', fieldName, 'with value:', value);
                        
                        // Special handling for country field
                        if (fieldName === 'billing_country') {
                            console.log('Special handling for billing_country field');
                            console.log('Field type:', $field.prop('tagName'));
                            console.log('Field classes:', $field.attr('class'));
                            
                            // Try different approaches for country field
                            $field.val(value);
                            $field.trigger('change');
                            
                            // Force trigger various events that WooCommerce might listen to
                            $field.trigger('chosen:updated');
                            $field.trigger('select2:select');
                            
                            // If it's a select2 field
                            if ($field.hasClass('select2-hidden-accessible')) {
                                console.log('Handling select2 country field');
                                try {
                                    $field.select2('val', value);
                                    $field.select2('trigger', 'change');
                                } catch(e) {
                                    console.log('Select2 method failed, trying alternative');
                                }
                            }
                            
                            // If it's a chosen field
                            if ($field.hasClass('chosen-select')) {
                                console.log('Handling chosen country field');
                                $field.trigger('chosen:updated');
                            }
                            
                            // Additional WooCommerce specific triggers
                            $field.trigger('country_to_state_changed');
                            $('body').trigger('country_to_state_changing', [value, $field]);
                            
                        } else {
                            // Regular field handling
                            $field.val(value).trigger('change');
                            
                            // General select2 handling for other fields
                            if ($field.hasClass('select2-hidden-accessible')) {
                                $field.select2('val', value);
                            }
                        }
                    } else {
                        console.log('Field not found:', fieldName);
                    }
                }
            });

            // Special retry for country field if it didn't work
            if (data.billing_country) {
                setTimeout(function() {
                    const $countryField = $('#billing_country, select[name="billing_country"]');
                    if ($countryField.length && $countryField.val() !== data.billing_country) {
                        console.log('Country field retry - current value:', $countryField.val());
                        console.log('Expected value:', data.billing_country);
                        
                        // Force set the value again
                        $countryField.val(data.billing_country);
                        $countryField.trigger('change');
                        
                        // Try additional WooCommerce events
                        $('body').trigger('country_to_state_changed');
                        $countryField.trigger('wc_address_i18n_ready');
                    }
                }, 1000);
            }
            
            // Trigger checkout update after autofill
            setTimeout(function() {
                $('body').trigger('update_checkout');
            }, 1500);
        },

        // Initialize checkout updates
        initCheckoutUpdates: function() {
            $(document.body).on('updated_checkout', this.onCheckoutUpdate.bind(this));
            $(document.body).on('checkout_error', this.onCheckoutError.bind(this));
        },

        // Handle checkout updates
        onCheckoutUpdate: function() {
            // Re-initialize VIP section after checkout updates
            this.initVipCodeSection();
        },

        // Handle checkout errors
        onCheckoutError: function() {
            // Clear VIP verification state if there are errors
            if (this.vipCodeVerified) {
                this.vipCodeVerified = false;
                localStorage.removeItem('wvp_vip_verified');
            }
        },

        // Initialize form validation
        initFormValidation: function() {
            const $checkoutForm = $('form.checkout');
            
            if ($checkoutForm.length) {
                $checkoutForm.on('checkout_place_order', this.validateVipCodeBeforeOrder.bind(this));
            }
        },

        // Validate VIP code before placing order
        validateVipCodeBeforeOrder: function() {
            const $vipInput = $('#wvp_code');
            
            if ($vipInput.length && $vipInput.val() && !this.vipCodeVerified) {
                // Prevent order submission
                e.preventDefault();
                const $messages = $('#wvp_code_messages');
                this.showMessage($messages, 'warning', 'Please verify your VIP code before placing the order.');
                
                $('html, body').animate({
                    scrollTop: $('#wvp-checkout-vip-section').offset().top - 100
                }, 500);
                
                return false;
            }
            
            return true;
        },

        // Initialize real-time price updates
        initPriceUpdates: function() {
            // Update prices when VIP status changes
            $(document.body).on('wvp_vip_status_changed', function() {
                $('body').trigger('update_checkout');
            });
        },

        // Initialize order review enhancements
        initOrderReview: function() {
            // Add any order review specific functionality here
        }
    };

    // Add email confirmation popup functionality to WVP_Checkout
    WVP_Checkout.showEmailConfirmationPopup = function(data) {
        console.log('WVP DEBUG: showEmailConfirmationPopup called with data:', data);
        const codeData = data.code_data;
        
        // Remove any existing popup first
        $('#wvp-email-confirmation-popup').remove();
        
        // Create data preview with masked sensitive info
        const dataPreview = `
            <div class="wvp-data-preview">
                <h4>Podaci za automatsko popunjavanje:</h4>
                <ul class="wvp-data-list">
                    ${codeData.first_name ? `<li><strong>Ime:</strong> ${codeData.first_name}</li>` : ''}
                    ${codeData.last_name ? `<li><strong>Prezime:</strong> ${codeData.last_name}</li>` : ''}
                    ${codeData.email ? `<li><strong>Email:</strong> ${codeData.email.replace(/(.{2}).*(@.*)/, '$1***$2')}</li>` : ''}
                    ${codeData.phone ? `<li><strong>Telefon:</strong> ${codeData.phone.replace(/(.{3}).*(.{3})$/, '$1****$2')}</li>` : ''}
                    ${codeData.company ? `<li><strong>Kompanija:</strong> ${codeData.company}</li>` : ''}
                    ${codeData.address_1 ? `<li><strong>Adresa:</strong> ${codeData.address_1}</li>` : ''}
                    ${codeData.city ? `<li><strong>Grad:</strong> ${codeData.city}</li>` : ''}
                    ${codeData.postcode ? `<li><strong>Poštanski broj:</strong> ${codeData.postcode}</li>` : ''}
                </ul>
            </div>
        `;
        
        const confirmationHtml = `
            <div id="wvp-email-confirmation-popup" class="wvp-modal-overlay">
                <div class="wvp-modal-content">
                    <div class="wvp-modal-header">
                        <h3>Potvrdi identitet za VIP kod ${codeData.code}</h3>
                        <button type="button" class="wvp-modal-close">&times;</button>
                    </div>
                    <div class="wvp-modal-body">
                        ${dataPreview}
                        <div class="wvp-verification-section">
                            <p><strong>Za potvrdu identiteta i automatsko popunjavanje forme, unesite:</strong></p>
                            
                            <div class="wvp-input-group">
                                <input type="text" 
                                       id="wvp_confirm_identity" 
                                       placeholder="${codeData.email && codeData.phone ? 'Unesite email ili telefon' : codeData.email ? 'Unesite email adresu' : 'Unesite broj telefona'}" 
                                       class="wvp-identity-input">
                                <button type="button" id="wvp_confirm_btn" class="wvp-confirm-button">
                                    Potvrdi i popuni formu
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Add to body
        $('body').append(confirmationHtml);
        
        console.log('WVP DEBUG: HTML appended to body');
        console.log('WVP DEBUG: Popup element exists?', $('#wvp-email-confirmation-popup').length);
        console.log('WVP DEBUG: Button element exists?', $('#wvp_confirm_btn').length);
        console.log('WVP DEBUG: Input element exists?', $('#wvp_confirm_identity').length);
        
        // Show popup
        $('#wvp-email-confirmation-popup').hide().fadeIn('slow');
        
        // Focus and setup handlers immediately after adding to DOM
        setTimeout(function() {
            const $input = $('#wvp_confirm_identity');
            const $button = $('#wvp_confirm_btn');
            
            console.log('WVP DEBUG: After timeout - input exists?', $input.length);
            console.log('WVP DEBUG: After timeout - button exists?', $button.length);
            
            if ($input.length) {
                $input.focus();
                console.log('WVP DEBUG: Input focused');
            }
            
            // Set up click handler immediately
            if ($button.length) {
                console.log('WVP DEBUG: Setting up button click handler');
                
                $button.off('click').on('click', function(e) {
                    console.log('WVP DEBUG: IMMEDIATE Button clicked!');
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const inputValue = $input.val();
                    console.log('WVP DEBUG: IMMEDIATE Input value:', inputValue);
                    
                    if (!inputValue || !inputValue.trim()) {
                        alert('Molimo unesite email ili telefon za potvrdu.');
                        return;
                    }
                    
                    // Call confirmation function directly
                    WVP_Checkout.processIdentityConfirmation(inputValue.trim(), codeData);
                });
                
                console.log('WVP DEBUG: Button click handler set up');
                
                // Also set up Enter key support
                $input.off('keypress').on('keypress', function(e) {
                    if (e.which === 13 || e.keyCode === 13) {
                        console.log('WVP DEBUG: Enter key pressed');
                        e.preventDefault();
                        $button.click();
                    }
                });
                
                console.log('WVP DEBUG: Enter key handler set up');
            }
        }, 100);
        
        // OLD FUNCTION - NOT USED ANYMORE - replaced with processIdentityConfirmation
        function confirmIdentity() {
            console.log('WVP DEBUG: confirmIdentity called');
            
            // Try multiple ways to get the input element
            const $input = $('#wvp_confirm_identity');
            const inputElement = document.getElementById('wvp_confirm_identity');
            const inputByName = document.querySelector('input[name*="identity"]');
            const allInputs = document.querySelectorAll('#wvp-email-confirmation-popup input[type="text"]');
            
            console.log('WVP DEBUG: jQuery input found?', $input.length);
            console.log('WVP DEBUG: getElementById found?', inputElement ? 'YES' : 'NO');
            console.log('WVP DEBUG: querySelector by name found?', inputByName ? 'YES' : 'NO');
            console.log('WVP DEBUG: all text inputs in popup:', allInputs.length);
            
            // Try multiple ways to get the value
            let identity = '';
            if ($input.length) {
                identity = $input.val();
                console.log('WVP DEBUG: jQuery val():', "'" + identity + "'");
            }
            if (inputElement) {
                console.log('WVP DEBUG: element.value:', "'" + inputElement.value + "'");
                if (!identity) identity = inputElement.value;
            }
            if (allInputs.length > 0) {
                console.log('WVP DEBUG: first input value:', "'" + allInputs[0].value + "'");
                if (!identity) identity = allInputs[0].value;
            }
            
            const trimmedIdentity = identity ? identity.trim() : '';
            console.log('WVP DEBUG: final trimmed identity value:', "'" + trimmedIdentity + "'");
            
            if (!trimmedIdentity) {
                console.log('WVP DEBUG: Empty identity, showing validation message');
                alert('Molimo unesite email ili telefon za potvrdu.');
                $('#wvp_confirm_identity').focus();
                return;
            }
            
            // Disable button to prevent double-clicking
            $('#wvp_confirm_btn').prop('disabled', true).text('Proverava...');
            
            // Determine if input is email or phone
            const isEmail = trimmedIdentity.includes('@');
            console.log('WVP DEBUG: isEmail:', isEmail);
            
            const ajaxData = {
                action: isEmail ? 'wvp_confirm_email_and_autofill' : 'wvp_confirm_phone_and_autofill',
                [isEmail ? 'email' : 'phone']: trimmedIdentity,
                code_id: codeData.id,
                nonce: wvp_public_ajax.nonce
            };
            
            console.log('WVP DEBUG: AJAX data:', ajaxData);
            
            $.ajax({
                url: wvp_public_ajax.ajax_url,
                type: 'POST',
                data: ajaxData,
                success: function(response) {
                    WVP_Checkout.handleConfirmationSuccess(response);
                },
                error: function(xhr, status, error) {
                    console.log('AJAX Error:', error);
                    alert('Greška prilikom potvrde. Molimo pokušajte ponovo.');
                },
                complete: function() {
                    // Re-enable button
                    $('#wvp_confirm_btn').prop('disabled', false).text('Potvrdi i popuni formu');
                }
            });
        }
        
        // OLD EVENT HANDLERS REMOVED - now handled immediately after DOM insertion
        
        // Enter key support
        $('#wvp_confirm_identity').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                confirmIdentity();
            }
        });
        
        // Handle close button
        $('#wvp-email-confirmation-popup .wvp-modal-close').on('click', function() {
            $('#wvp-email-confirmation-popup').fadeOut('slow', function() {
                $(this).remove();
            });
        });
    };
    
    // Process identity confirmation with simplified approach
    WVP_Checkout.processIdentityConfirmation = function(identity, codeData) {
        console.log('WVP DEBUG: processIdentityConfirmation called');
        console.log('WVP DEBUG: Identity:', identity);
        console.log('WVP DEBUG: Code data:', codeData);
        
        // Disable button
        $('#wvp_confirm_btn').prop('disabled', true).text('Proverava...');
        
        // Determine if input is email or phone
        const isEmail = identity.includes('@');
        console.log('WVP DEBUG: Is email?', isEmail);
        
        const ajaxData = {
            action: isEmail ? 'wvp_confirm_email_and_autofill' : 'wvp_confirm_phone_and_autofill',
            [isEmail ? 'email' : 'phone']: identity,
            code_id: codeData.id,
            nonce: wvp_public_ajax.nonce
        };
        
        console.log('WVP DEBUG: Sending AJAX request:', ajaxData);
        
        $.ajax({
            url: wvp_public_ajax.ajax_url,
            type: 'POST',
            data: ajaxData,
            success: function(response) {
                console.log('WVP DEBUG: AJAX success response:', response);
                WVP_Checkout.handleConfirmationSuccess(response);
            },
            error: function(xhr, status, error) {
                console.log('WVP DEBUG: AJAX error:', error);
                console.log('WVP DEBUG: XHR:', xhr);
                alert('Greška prilikom potvrde. Molimo pokušajte ponovo.');
            },
            complete: function() {
                // Re-enable button
                $('#wvp_confirm_btn').prop('disabled', false).text('Potvrdi i popuni formu');
            }
        });
    };

    // Handle successful confirmation response
    WVP_Checkout.handleConfirmationSuccess = function(response) {
        if (response.success) {
            // Close popup after success
            setTimeout(function() {
                $('#wvp-email-confirmation-popup').fadeOut('fast', function() {
                    $(this).remove();
                });
                
                // Hide the VIP code section
                $('#wvp-checkout-vip-section').slideUp();
                
                // Show success message
                $('#wvp_code_messages').html('<div class="wvp-message success">VIP status aktiviran! Forma je popunjena.</div>');
                
                // Auto-fill form with returned data
                if (response.data.autofill_data) {
                    setTimeout(function() {
                        WVP_Checkout.autofillBillingForm(response.data.autofill_data);
                    }, 500);
                }
                
                // Force checkout refresh
                $('body').trigger('update_checkout');
                
                // If refresh is needed, reload page
                if (response.data.refresh_checkout) {
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                }
            }, 500);
        } else {
            alert(response.data.message || 'Greška prilikom potvrde identiteta');
        }
    };

    // Initialize checkout functionality
    WVP_Checkout.init();

    // Make available globally
    window.WVP_Checkout = WVP_Checkout;
});