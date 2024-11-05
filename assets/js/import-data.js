jQuery(document).ready(function($){
    $('#btn-import-data').click(function(){

        if ($(this).hasClass('disabled')) {
            return false;
        }

        let inputFile = $('input[name="json-file"]');

        if(inputFile.val() == '') {
            alert('Please update a json file');
            return false;
        }

        let formData = new FormData();
        formData.append('json-file', inputFile[0].files[0]);

        formData.append('action', 'itc_md_admin_request');
        formData.append('method', 'import_data');

        // Send the Ajax request.
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            contentType: false,
            cache: false,
            processData: false,
            beforeSend: function(xhr){
                $('#btn-import-data').addClass('disabled');
                $('#loading-progress').css('display','inline-block');

            },
            complete: function() {
                $('#loading-progress').hide();
                $('#btn-import-data').removeClass('disabled');
            },
            success: function(response) {
                if (response.msg) {
                    alert(response.msg);
                    inputFile.val('');
                }
            },
            error: function(xhr, status, error) {
                alert(error);
            }
        });
    });
});