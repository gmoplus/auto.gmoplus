<!-- banners upload manager responsive -->
<div class="text-notice">{$lang.max_file_size_caption} <b>{$max_file_size} MB</b></div>

<div id="fileupload">
    <form class="upload-zone"
          style="width: {$boxInfo.width}px; height: {$boxInfo.height}px;overflow: visible;max-width: 100%;"
          onsubmit="return false;"
          action="{$smarty.const.RL_PLUGINS_URL}banners/upload/account.php"
          method="post"
          encoding="multipart/form-data"
          enctype="multipart/form-data">
        <input type="file" name="files" style="top:0;width:{$boxInfo.width}px;height:{$boxInfo.height}px"/>
        <input type="hidden" name="box_width" value="{$boxInfo.width}" />
        <input type="hidden" name="box_height" value="{$boxInfo.height}" />

        <div class="files canvas"></div>

        <span>{$boxInfo.width} x {$boxInfo.height}</span>
    </form>
</div>

<!-- The template to display files available for upload -->
<script id="template-upload" type="text/x-tmpl">
<span style="height: {literal}{%=photo_height%}{/literal}px;" class="template-upload item active"></span>
</script>

<!-- The template to display files available for download -->
<script id="template-download" type="text/x-tmpl">{literal}
{% if (file = o.files[0]) { %}
    <img class="thumbnail" src="{%=file.thumbnail_url%}" />
    <img data-type="{%=file.delete_type%}" data-url="{%=file.delete_url%}" src="{/literal}{$rlTplBase}{literal}img/blank.gif" class="delete" alt="{/literal}{$lang.delete}{literal}" title="{/literal}{$lang.delete}{literal}" style="width: 17px;height: 17px;background:url('{/literal}{$rlTplBase}{literal}img/gallery.png') right -489px no-repeat;cursor:pointer;position:absolute;top:0;right:-25px;" />
{% } %}
</script>{/literal}

<style type="text/css">{literal}
.upload-zone div.files.canvas:not(:empty) + span {
    display: none;
}
</style>{/literal}

{assign var='limitVar' value=`$smarty.ldelim`limit`$smarty.rdelim`}

<script>
var rlPlugins = '{$smarty.const.RL_PLUGINS_URL}';
var photo_allowed = 1;
var photo_width = {$boxInfo.width};
var photo_height = {$boxInfo.height};
var photo_max_size = {if $max_file_size}{$max_file_size}{else}2{/if}*1024*1024;
var photo_auto_upload = true;
lang['error_maxFileSize'] = "{$lang.error_maxFileSize}";
lang['error_acceptFileTypes'] = "{$lang.error_acceptFileTypes}";
lang['uploading_completed'] = "{$lang.uploading_completed}";
lang['upload'] = "{$lang.upload}";
lang['banners_unsaved_photos_notice'] = '{$lang.banners_unsaved_photos_notice}';
lang['error_maxFileSize'] = '{$lang.error_maxFileSize|replace:$limitVar:$max_file_size}';
lang['error_upload_banner'] = "{$lang.banners_error_upload_banner}";

var ph_empty_error = "{$lang.crop_empty_coords}";
var ph_too_small_error = "{$lang.crop_too_small}";

{literal}
var managePhotoDesc = function() {};
var crop_handler = function() {};
var isBannerLoaded = function() {
    return !!$('div#fileupload div.files > img.thumbnail').length;
};
var submit_photo_step = function() {
    if ('image' === $('input[name=banner_type]').val() && !isBannerLoaded()) {
        printMessage('error', lang['banners_unsaved_photos_notice']);

        return false;
    }
    return true;
};

$(document).ready(function () {
    var $uploadZone = $('form.upload-zone');

    $uploadZone.fileupload({
        maxNumberOfFiles: photo_allowed,
        maxFileSize: rlConfig.upload_max_size,
        acceptFileTypes: /^image\/(gif|jpe?g|png)$/,
        autoUpload: true
    }).on('fileuploadcompleted', function () {
        if (isBannerLoaded()) {
            $uploadZone.find('input[type=file]').attr('disabled', true).css('cursor', 'default');
        }
    }).on('fileuploaddestroy', function () {
        $uploadZone.find('input[type=file]').attr('disabled', false).css('cursor', 'pointer');
        $uploadZone.find('div.files').html('');
    }).on('fileuploadfail', function () {
        printMessage('error', lang.error_upload_banner);
        $uploadZone.find('div.files').html('');
    });

    $.getJSON(rlPlugins +'banners/upload/account.php', function (files) {
        $uploadZone.fileupload('option', 'done').call($('.upload-zone'), null, {result: files});
    });
});
{/literal}
</script>

{addJS file=$smarty.const.RL_LIBS_URL|cat:'upload/tmpl.min.js'}
{addJS file=$smarty.const.RL_LIBS_URL|cat:'upload/jquery.ui.widget.js'}
{addJS file=$smarty.const.RL_LIBS_URL|cat:'upload/jquery.iframe-transport.js'}
{addJS file=$smarty.const.RL_LIBS_URL|cat:'upload/jquery.fileupload.js'}
{addJS file=$smarty.const.RL_LIBS_URL|cat:'upload/jquery.fileupload-ui.js'}
{addJS file=$smarty.const.RL_LIBS_URL|cat:'upload/exif.js'}

<!-- banners upload manager responsive end -->
