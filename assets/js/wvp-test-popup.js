// Test popup functionality
console.log('WVP TEST: Test script loaded');

// Immediate test when script loads
setTimeout(function() {
    console.log('WVP TEST: Looking for popup elements...');
    
    // Try to find popup elements
    const popup = document.querySelector('#wvp-email-confirmation-popup');
    const button = document.querySelector('#wvp_confirm_btn');
    const input = document.querySelector('#wvp_confirm_identity');
    
    console.log('WVP TEST: Popup found:', popup ? 'YES' : 'NO');
    console.log('WVP TEST: Button found:', button ? 'YES' : 'NO'); 
    console.log('WVP TEST: Input found:', input ? 'YES' : 'NO');
    
    if (button) {
        console.log('WVP TEST: Adding click handler to button');
        button.addEventListener('click', function() {
            console.log('WVP TEST: Button clicked!');
            
            if (input) {
                const value = input.value;
                console.log('WVP TEST: Input value:', value);
                
                if (value && value.trim()) {
                    alert('SUCCESS: Vrednost je: ' + value);
                } else {
                    alert('ERROR: Nema vrednosti u input polju');
                }
            } else {
                alert('ERROR: Input polje nije pronađeno');
            }
        });
    }
}, 2000);

// Also try with jQuery if available
if (typeof jQuery !== 'undefined') {
    jQuery(document).ready(function($) {
        console.log('WVP TEST: jQuery ready');
        
        // Event delegation for dynamic elements
        $(document).on('click', '#wvp_confirm_btn', function() {
            console.log('WVP TEST: jQuery button click detected');
            const value = $('#wvp_confirm_identity').val();
            console.log('WVP TEST: jQuery input value:', value);
            alert('jQuery SUCCESS: ' + value);
        });
    });
}