jQuery(document).ready(function($){
    function validateSyncButton() {
        var clientid = $('#woocommerce_tropipay_clientid').val();
        var clientsecret = $('#woocommerce_tropipay_clientsecret').val();
        var $btn = $('#tropipay_sync_btn');
        var $warning = $('#tropipay_sync_warning');
        
        if (!clientid || !clientsecret) {
            $btn.prop('disabled', true).addClass('disabled').css('cursor', 'not-allowed');
            $warning.text(tropipay_admin_params.txt_warning).css('color', '#a00');
        } else {
            $btn.prop('disabled', false).removeClass('disabled').css('cursor', 'pointer');
            $warning.text('');
        }
    }

    function toggleFeeFields() {
        var addFees = $('#woocommerce_tropipay_tropipayaddFees').val();
        var $feeFields = $('#woocommerce_tropipay_tropipayfeecardpercent, #woocommerce_tropipay_tropipayfeecardfixed, #woocommerce_tropipay_tropipayfeebalancepercent, #woocommerce_tropipay_tropipayfeebalancefixed, #woocommerce_tropipay_tropipayshowfees');
        
        $feeFields.each(function(){
            var $row = $(this).closest('tr');
            if (addFees === 'si') {
                $row.show();
            } else {
                $row.hide();
            }
        });
    }

    // Validate on load & input
    validateSyncButton();
    $('#woocommerce_tropipay_clientid, #woocommerce_tropipay_clientsecret').on('input change', validateSyncButton);

    // Toggle fees on load & change
    toggleFeeFields();
    $('#woocommerce_tropipay_tropipayaddFees').on('change', toggleFeeFields);

    // Handle AJAX sync
    $('#tropipay_sync_btn').on('click', function(e){
        e.preventDefault();
        var $btn = $(this);
        var $status = $('#tropipay_sync_status');
        
        $btn.prop('disabled', true).text(tropipay_admin_params.txt_syncing);
        $status.text('').css('color', 'inherit');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'tropipay_sync_accounts',
                nonce: tropipay_admin_params.sync_nonce
            },
            success: function(response) {
                if (response.success) {
                    $status.text(tropipay_admin_params.txt_synced).css('color', 'green');
                    setTimeout(function(){
                        window.location.reload();
                    }, 1500);
                } else {
                    $status.html(response.data || tropipay_admin_params.txt_err_sync).css('color', 'red');
                    $btn.prop('disabled', false).text(tropipay_admin_params.txt_sync_btn);
                }
            },
            error: function() {
                $status.text(tropipay_admin_params.txt_err_net).css('color', 'red');
                $btn.prop('disabled', false).text(tropipay_admin_params.txt_sync_btn);
            }
        });
    });
});
