<form id="rcb-comments-import-settings-form" class="" action="" method="post" data-rcb-comments-validate="true">
    <?php wp_nonce_field( 'verify_rcb_comments_import_nonce', 'rcb_comments_import_nonce' ); ?>
    <input type="hidden" name="status" value="startImport">
    <p class="submit">
        <input type="submit" class="button button-primary" value="Выгрузить комментарии из Recobox в Worpdress"/>
    </p>
    <table class="wp-list-table widefat fixed striped" id="recobox-table" style="display: none">
        <thead>
        <tr>
            <th style="text-align: center">Не рекомендуется закрывать браузер пока не завершится процесс</th>
        </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</form>
<script>
    document.addEventListener("DOMContentLoaded", function (event) {
        jQuery(function($) {
            $(document).on('submit', '#rcb-comments-import-settings-form', function () {
                var _this = $(this);
                if(_this.hasClass('processed')) {
                    return false;
                } else {
                    _this.addClass('processed');
                }

                $('#recobox-table tbody').html('');

                $('#recobox-table').removeAttr('style');


                ajaxImport(_this, {
                    action: 'import_channels',
                    offset: 0,
                    post_id: 0
                });

                return false;
            });

            function ajaxImport(_this, data) {

                loadRow();

                $.ajax({
                    type: "POST",
                    url: ajaxurl,
                    dataType: "json",
                    data: data,
                    success: function (resp) {
                        console.log('Success', resp);
                        removeSpinner();

                        if(resp.result && resp.result == 'error') {
                            _this.removeClass('processed');
                            addError(resp.text);
                            return false;
                        }

                        if(resp.tr) {
                            $.each(resp.tr, function(i, v) {
                                loadedRow(v);
                            })
                        }

                        if(resp.result && resp.result == 'finish') {
                            _this.removeClass('processed');
                        } else {
                            ajaxImport(_this, resp);
                        }
                    },
                    error: function (resp) {
                        console.log('Error', resp);
                    },
                    complete: function () {
                        //_this.removeClass('processed');
                    }
                });
            }

            function addError(text) {
                $('#recobox-table tbody').append('<tr>' +
                    '<td style="color:red;">' + text + '</td>' +
                    '</tr>');
            }

            function loadedRow(tr) {
                $('#recobox-table tbody').append('<tr>' +
                    '<' + tr.tag + '>' + tr.text + '</' + tr.tag + '>' +
                    '</tr>');
            }

            function loadRow() {
                $('#recobox-table tbody').append('<tr class="recobox-spinner-tr">' +
                    '<td>' +
                    '<div class="recobox-spinner"></div>' +
                    '</td>' +
                    '</tr>');
            }

            function removeSpinner() {
                $('#recobox-table tbody .recobox-spinner-tr').remove();
            }
        });
    });
</script>
<style>
    .recobox-spinner {
        background: url('/wp-admin/images/wpspin_light-2x.gif') no-repeat;
        width: 32px;
        height: 32px;
        margin: 0px auto;
    }
</style>