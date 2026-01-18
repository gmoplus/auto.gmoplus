<!-- attache resume tpl -->

<div class="submit-cell">
    <div class="field">
        <div class="file-input d-flex">
            <input type="hidden" name="resume_file" value="" />
            <input class="file w-100" id="attached_file_{$captcha_box_id}" type="file" name="resume" />
            <span>{$lang.choose}</span>
            <div class="flex-fill"><input autocomplete="off" type="text" id="fake_attached_file_{$captcha_box_id}" class="file-name w-100" placeholder="{$lang.attach_resume}" /></div>
        </div>
    </div>
</div>

{addJS file=$smarty.const.RL_LIBS_URL|cat:'jquery/upload/jquery.ui.widget.js'}
{addJS file=$smarty.const.RL_LIBS_URL|cat:'jquery/upload/jquery.iframe-transport.js'}
{addJS file=$smarty.const.RL_LIBS_URL|cat:'jquery/upload/jquery.fileupload.js'}
{addJS file=$smarty.const.RL_LIBS_URL|cat:'jquery/upload/jquery.fileupload-ui.js'}

<script class="fl-js-dynamic">
lang['invalid_file_extension'] = "{$lang.invalid_file_extension}";
lang['notice_field_empty'] = "{$lang.notice_field_empty}";
lang['attach_resume'] = "{$lang.attach_resume}";

{literal}

$(function(){
    "use strict";

    $('input[name=resume]').closest('form').each(function(){
        var self = this;
        var required_fields = ['contact_name', 'contact_email', 'contact_message', 'security_code'];
        var allow_submit = false;
        var $button = $(this).find('input[name=finish]');

        // File upload handler
        $(this).find('input[name=resume]').fileupload({
            url: rlConfig['ajax_url'] + '?mode=attachResume',
            dataType: 'json',
            autoUpload: true,
            singleFileUploads: true,
            add: function(e, data) {
                $(self).find('.file-name').val(data.files[0].name).removeClass('error');
                allow_submit = false;
                data.submit();
            },
            getFilesFromResponse: function(data) {
                if (data.result.status == 'OK') {
                    allow_submit = true;
                } else {
                    printMessage('error', lang['system_error']);
                }
            },
            fail: function(e, data) {
                printMessage('error', lang['system_error']);
            }
        });

        $(this).submit(function(e){
            var $fileInput = $(this).find('.file-name');
            var file_name = $fileInput.val();

            if (file_name) {
                var valid_ext = ['jpg', 'png', 'pdf', 'doc', 'docx', 'zip', 'rar'];
                var file_ext = file_name.substr(file_name.lastIndexOf('.') + 1);

                if ($.inArray(file_ext, valid_ext) == -1){
                    printMessage(
                        'error',
                        lang['invalid_file_extension'].replace('{ext}', '"' + valid_ext.join(', ') + '"'),
                        '#' + $(this).find('input.file-name').attr('id')
                    );
                    return false;
                } else {
                    $button.val(lang['loading']);
                }
            } else {
                if ($(this).closest('.popup').length) {
                    $fileInput.addClass('error');
                } else {
                    printMessage(
                        'error',
                        lang['notice_field_empty'].replace('{field}', lang['attach_resume']),
                        '#' + $fileInput.attr('id')
                    );
                }
                return false;
            }

            return allow_submit;
        });
    });
});

{/literal}
</script>

<!-- attache resume tpl end -->
