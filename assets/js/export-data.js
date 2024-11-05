jQuery(document).ready(function($){
    $('#available-post-types .selected-post-type').on('change', function(){
        let postTypeWrapper = $(this).closest('.item-post-type');

        if( $(this).is(':checked') ) {
            postTypeWrapper.find('.select-posts-wpr').fadeIn();
        }else {
            postTypeWrapper.find('.select-posts-wpr').fadeOut();
        }
    });

    let defaultSelect2Options = {
        allowClear: true,
        multiple : true,
        closeOnSelect: false,
        ajax: {
            delay: 250,
            url: ajaxurl,
            data: function (params) {
              return {
                action: 'itc_md_admin_request',
                method: 'get_posts_by_post_type',
                post_params : {
                    post_type: $(this).attr('data-post-type'),
                    search: params.term,
                    page: params.page || 1,
                }
              };
            }
         }
    }

    $('#available-post-types .post-ids-list')
        .select2(defaultSelect2Options)
        .on('select2:select', function (e) {
    });

    $('#btn-export-data').click(function(){

        if ($(this).hasClass('disabled')) {
            return false;
        }

        let exportData = {};
        $('#available-post-types .item-post-type').each(function(){
            if ($(this).find('.selected-post-type').is(':checked')) {
                let postTypeName = $(this).find('.selected-post-type').val();
                exportData[postTypeName] = $(this).find('.post-ids-list').val();
            }
        });

        $.ajax({
            url: ajaxurl,
            method: 'GET',
            data: {
                action: 'itc_md_admin_request',
                method: 'export_data',
                export_data : exportData
            },
            beforeSend: function(xhr){
                $('#btn-export-data').addClass('disabled');
                $('#loading-progress').css('display','inline-block');

            },
            complete: function() {
                $('#loading-progress').hide();
                $('#btn-export-data').removeClass('disabled');
            },
            success: function(res) {
                if (res.status && res.status == 'error') {
                    alert(res.msg);
                    return false;
                }

                // Create a download link for the JSON content.
                var downloadLink = document.createElement('a');
                downloadLink.href = window.URL.createObjectURL(new Blob([res]));
                downloadLink.download = 'export-data.json';

                // Force download.
                document.body.appendChild(downloadLink);
                downloadLink.click();

            },
            error: function(xhr, status, error) {
                alert('error');
            }
        });
    });

});