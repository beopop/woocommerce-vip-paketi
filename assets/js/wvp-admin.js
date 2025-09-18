/**
 * WVP Admin JavaScript
 */

jQuery(document).ready(function($) {
    'use strict';

    // Check if wvp_admin_ajax is available
    if (typeof wvp_admin_ajax === 'undefined') {
        console.error('WVP Admin Ajax object not available');
        return;
    }

    // Generate random VIP code
    $('#generate-random-code').on('click', function(e) {
        e.preventDefault();
        const randomCode = 'VIP' + Math.random().toString(36).substr(2, 8).toUpperCase();
        $('#code').val(randomCode);
    });

    // File upload functionality
    const $uploadArea = $('.wvp-file-upload-area');
    const $fileInput = $('#csv_file');
    
    if ($uploadArea.length && $fileInput.length) {
        // Click to select file
        $uploadArea.on('click', function() {
            $fileInput.click();
        });

        // File input change
        $fileInput.on('change', function() {
            handleFileSelect(this.files);
        });

        // Drag and drop
        $uploadArea[0].addEventListener('dragover', handleDragOver, false);
        $uploadArea[0].addEventListener('drop', handleDrop, false);
        $uploadArea[0].addEventListener('dragenter', handleDragEnter, false);
        $uploadArea[0].addEventListener('dragleave', handleDragLeave, false);
    }

    function handleFileSelect(files) {
        if (files && files.length > 0) {
            const file = files[0];
            if (file.type === 'text/csv' || file.name.endsWith('.csv')) {
                $uploadArea.find('span').last().text(file.name);
                $uploadArea.addClass('file-selected');
            } else {
                showMessage('Molimo odaberite CSV fajl.', 'error');
                $fileInput.val('');
            }
        } else {
            $uploadArea.find('span').last().text('Odabrani fajl će se prikazati ovde');
            $uploadArea.removeClass('file-selected');
        }
    }

    function handleDragOver(e) {
        e.preventDefault();
        e.stopPropagation();
        $uploadArea.addClass('dragover');
    }

    function handleDragEnter(e) {
        e.preventDefault();
        e.stopPropagation();
        $uploadArea.addClass('dragover');
    }

    function handleDragLeave(e) {
        e.preventDefault();
        e.stopPropagation();
        $uploadArea.removeClass('dragover');
    }

    function handleDrop(e) {
        e.preventDefault();
        e.stopPropagation();
        $uploadArea.removeClass('dragover');
        
        const files = e.dataTransfer.files;
        $fileInput[0].files = files;
        $fileInput.trigger('change');
    }

    // Add new VIP code form
    $('#wvp-add-code-form').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');
        const originalBtnText = $submitBtn.html();
        
        // Disable submit button and show loading
        $submitBtn.prop('disabled', true).text('Dodajem...');
        
        const formData = {
            action: 'wvp_add_code',
            nonce: wvp_admin_ajax.nonce,
            code: $('#code').val().toUpperCase(),
            email: $('#email').val(),
            domain: $('#domain').val(),
            max_uses: $('#max_uses').val(),
            expires_at: $('#expires_at').val(),
            status: $('#status').val()
        };
        
        $.post(wvp_admin_ajax.ajax_url, formData)
            .done(function(response) {
                if (response.success) {
                    showMessage(response.data.message, 'success');
                    $form[0].reset();
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    showMessage(response.data, 'error');
                }
            })
            .fail(function() {
                showMessage('Došlo je do greške. Molimo pokušajte ponovo.', 'error');
            })
            .always(function() {
                $submitBtn.prop('disabled', false).html(originalBtnText);
            });
    });

    // Bulk import form
    $('#wvp-bulk-import-form').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');
        const originalBtnText = $submitBtn.html();
        
        if (!$fileInput[0].files.length) {
            showMessage('Molimo odaberite CSV fajl.', 'error');
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'wvp_bulk_import_codes');
        formData.append('nonce', wvp_admin_ajax.nonce);
        formData.append('csv_file', $fileInput[0].files[0]);
        
        // Show loading state
        $submitBtn.prop('disabled', true).text('Uvozim...');
        $form.addClass('wvp-loading');
        
        $.ajax({
            url: wvp_admin_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showMessage(response.data.message, 'success');
                    $form[0].reset();
                    $uploadArea.find('span').last().text('Odabrani fajl će se prikazati ovde');
                    $uploadArea.removeClass('file-selected');
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    showMessage(response.data, 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Ajax Error:', status, error);
                showMessage('Došlo je do greške tokom upload-a. Molimo pokušajte ponovo.', 'error');
            },
            complete: function() {
                $submitBtn.prop('disabled', false).html(originalBtnText);
                $form.removeClass('wvp-loading');
            }
        });
    });

    // Edit code functionality
    $('.wvp-edit-code').on('click', function(e) {
        e.preventDefault();
        const codeId = $(this).data('code-id');
        // Implementation for editing code
        console.log('Edit code:', codeId);
    });

    // Delete code functionality
    $('.wvp-delete-code').on('click', function(e) {
        e.preventDefault();
        const codeId = $(this).data('code-id');
        
        if (!confirm('Da li ste sigurni da želite da obrišete ovaj kod?')) {
            return;
        }
        
        const $btn = $(this);
        $btn.prop('disabled', true);
        
        $.post(wvp_admin_ajax.ajax_url, {
            action: 'wvp_delete_code',
            nonce: wvp_admin_ajax.nonce,
            code_id: codeId
        })
        .done(function(response) {
            if (response.success) {
                showMessage(response.data.message, 'success');
                setTimeout(function() {
                    location.reload();
                }, 1000);
            } else {
                showMessage(response.data, 'error');
                $btn.prop('disabled', false);
            }
        })
        .fail(function() {
            showMessage('Došlo je do greške. Molimo pokušajte ponovo.', 'error');
            $btn.prop('disabled', false);
        });
    });

    // Download CSV template
    $('#download-csv-template').on('click', function(e) {
        e.preventDefault();
        
        const csvContent = 'code,email,domain,first_name,last_name,company,phone,address_1,address_2,city,state,postcode,country,max_uses,membership_expires_at,expires_at,auto_renewal,status\n' +
                          'VIP123,user@example.com,example.com,Marko,Petrović,ABC DOO,065123456,Kraljevića Marka 15,stan 12,Beograd,Srbija,11000,RS,5,2025-12-31 23:59:59,2024-12-31 23:59:59,1,active\n' +
                          'PREMIUM456,ana@test.com,,Ana,Jovanović,,064987654,Njegoševa 20,,Novi Sad,Vojvodina,21000,RS,10,,,0,active';
        
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        
        const link = document.createElement('a');
        link.href = url;
        link.download = 'vip-codes-template.csv';
        link.style.display = 'none';
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        URL.revokeObjectURL(url);
    });

    // Show message function
    function showMessage(message, type) {
        const $messagesContainer = $('.wvp-messages');
        const messageClass = type === 'success' ? 'notice-success' : 'notice-error';
        
        $messagesContainer.html(
            '<div class="notice ' + messageClass + ' is-dismissible">' +
                '<p>' + message + '</p>' +
            '</div>'
        );
        
        // Scroll to message
        $('html, body').animate({
            scrollTop: $messagesContainer.offset().top - 50
        }, 500);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $messagesContainer.find('.notice').fadeOut();
        }, 5000);
    }

    // Code input uppercase transformation
    $('#code').on('input', function() {
        this.value = this.value.toUpperCase();
    });

    // Form validation
    $('#code').on('blur', function() {
        const code = $(this).val();
        if (code && !/^[A-Z0-9\-]+$/.test(code)) {
            showMessage('VIP kod može sadržavati samo slova, brojeve i crtice.', 'error');
            $(this).focus();
        }
    });

    // Tab switching (if needed for other admin pages)
    $('.nav-tab').on('click', function(e) {
        const href = $(this).attr('href');
        if (href && href.indexOf('#') === -1) {
            // Let default navigation work
            return;
        }
        e.preventDefault();
    });

    // Initialize tooltips if available
    if ($.fn.tooltip) {
        $('[data-tooltip]').tooltip();
    }

    console.log('WVP Admin script loaded successfully');
});