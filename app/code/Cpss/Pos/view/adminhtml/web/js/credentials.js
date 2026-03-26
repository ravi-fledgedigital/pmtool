require(['jquery', 'domReady!'], function ($) {
    const groups = ['pos', 'cpss', 'real_store'];

    $( document ).ready(function() {
        onloadPage(groups);
    });

    groups.forEach(group => {

        $(document).on('change', '#sftp_' + group + '_method_access', function() {
            let typeSelected = $("#row_sftp_" + group + "_private_key_selected #sftp_" + group + "_private_key_selected").val();
            
            if (this.value === "key") {
                if (typeSelected == 'pem') hide(group, 3);
                else hide(group, 1);
            }
            else hide(group, 4);
        });

        // private key select pem file
        $('#row_sftp_' + group + '_private_key_selector #private_key_pem_button').click(function (e) {
            e.preventDefault();
            hide(group, 3);
            $('#row_sftp_' + group + '_private_key_selected #sftp_' + group + '_private_key_selected').val('pem');
            $('#row_sftp_' + group + '_private_key_pem #note_private_key_pem_file_saved_msg').html('');
        });

        // private key select text
        $('#row_sftp_' + group + '_private_key_selector #private_key_text_button').click(function (e) {
            e.preventDefault();
            hide(group, 1);
            $('#row_sftp_' + group + '_private_key_selected #sftp_' + group + '_private_key_selected').val('text');
            $('#sftp_' + group + '_private_key_text').val('').focus();
        });

        // change key type
        $('.' + group + '-private-key-change-key-type').click(function (e) {
            e.preventDefault();
            hide(group, 2);
        });
    });

    function onloadPage(groups) {
        groups.forEach(group => {
            let methodAccess = $("#sftp_" + group + "_method_access").val();
            
            if (methodAccess === "key") {
                hide(group, 1);
            } 
            else hide(group, 4);
        });
    }

    function hide(group, value) {
        switch (value) {
            case 1: //show textarea
                $('#row_sftp_' + group + '_private_key_pem').hide();
                $('#row_sftp_' + group + '_private_key_selected').hide();
                $('#row_sftp_' + group + '_private_key_selector').hide();
                $('#row_sftp_' + group + '_private_key_text').show();
                break;
            case 2: //show selector
                $('#row_sftp_' + group + '_private_key_pem').hide();
                $('#row_sftp_' + group + '_private_key_text').hide();
                $('#row_sftp_' + group + '_private_key_selected').hide();
                $('#row_sftp_' + group + '_private_key_selector').show();
                $('#sftp_' + group + '_private_key_text').val('------');
                break;
            case 3: //show pem upload
                $('#row_sftp_' + group + '_private_key_text').hide();
                $('#row_sftp_' + group + '_private_key_selected').hide();
                $('#row_sftp_' + group + '_private_key_selector').hide();
                $('#row_sftp_' + group + '_private_key_pem').show();
                $('#sftp_' + group + '_private_key_text').val('------');
                break;
            case 4: //hide all
                $('#row_sftp_' + group + '_private_key_text').hide();
                $('#row_sftp_' + group + '_private_key_selected').hide();
                $('#row_sftp_' + group + '_private_key_selector').hide();
                $('#row_sftp_' + group + '_private_key_pem').hide();
                $('#sftp_' + group + '_private_key_text').val('------');
                break;
        }
    }
});
