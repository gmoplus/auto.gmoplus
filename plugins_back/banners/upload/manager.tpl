<!-- banners upload manager -->

<div class="dark">{$lang.max_file_size_caption} <b>{$max_file_size} MB</b></div>
<div id="fileupload">
    <form class="upload-zone"
          onsubmit="return false;"
          action="{$smarty.const.RL_PLUGINS_URL}banners/upload/admin.php"
          method="post"
          encoding="multipart/form-data"
          enctype="multipart/form-data">
        <span class="files canvas"></span>
        <span title="{$lang.add_photo}" class="draft fileinput-button">
            <span id="size-notice"><b>{$sBox.width}</b> x <b>{$sBox.height}</b></span>
            <input type="file" name="files" style="width:{$sBox.width}px;height:{$sBox.height}px;" />
            <input type="hidden" name="box_width" value="{$sBox.width}" />
            <input type="hidden" name="box_height" value="{$sBox.height}" />
        </span>
    </form>
</div>

{literal}
<!-- The template to display files available for upload -->
<script id="template-upload" type="text/x-tmpl">
<span class="preview"></span>
</script>
<!-- The template to display files available for download -->
<script id="template-download" type="text/x-tmpl">
{% if (file = o.files[0]) { %}
<span style="height: {%=photo_height%}px;" class="template-download item active">
    <img class="thumbnail" src="{%=file.thumbnail_url%}" />
    <img data-type="{%=file.delete_type%}" data-url="{%=file.delete_url%}" src="{/literal}{$rlTplBase}img/blank.gif" class="delete" alt="{$lang.delete}" title="{$lang.delete}" />
    <img src="{$rlTplBase}{literal}img/blank.gif" alt="" class="loaded" />
</span>
{% } %}
</script>
{/literal}

<style type="text/css" title="banners">
div#fileupload span.progress
{literal}{{/literal}
    margin: 0;
{literal}}{/literal}

div#fileupload span.hover
{literal}{{/literal}
    width: {$sBox.width}px;
    height: {$sBox.height}px;
{literal}}{/literal}

div#fileupload span.draft
{literal}{{/literal}
    width: {$sBox.width}px;
    height: {$sBox.height}px;
    line-height: {$sBox.height}px;
    padding: 0;
    margin: 0 10px 5px 0;
    background: #F3F3F3;
{literal}}{/literal}

canvas.new, img.thumbnail
{literal}{{/literal}
    width: {$sBox.width}px;
    height: {$sBox.height}px;
{literal}}{/literal}

div#fileupload span.active, div#fileupload span.hover
{literal}{{/literal}
    width: {$sBox.width+4}px;
    height: {$sBox.height}px;
{literal}}{/literal}

div#fileupload img.loaded
{literal}{{/literal}
    margin: 0 4px 4px;
{literal}}{/literal}
</style>

{assign var='limitVar' value=`$smarty.ldelim`limit`$smarty.rdelim`}

<script type="text/javascript">
var photo_allowed = 1;
var photo_width, photo_orig_width = {$sBox.width};
var photo_height, photo_orig_height = {$sBox.height};
var photo_max_size, photo_client_max_size = {if $max_file_size}{$max_file_size}{else}2{/if}*1024*1024;
var photo_auto_upload = true;
var client_resize, photo_user_crop = false;

lang['error_maxFileSize'] = '{$lang.error_maxFileSize|replace:$limitVar:$max_file_size}';
lang['error_acceptFileTypes'] = "{$lang.error_acceptFileTypes}";
lang['uploading_completed'] = "{$lang.uploading_completed}";
lang['error_upload_banner'] = "{$lang.banners_error_upload_banner}";
lang['upload'] = "{$lang.upload}";

var ph_empty_error = "{$lang.crop_empty_coords}";
var ph_too_small_error = "{$lang.crop_too_small}";

{literal}
var managePhotoDesc = function() {};
var crop_handler = function() {};

$(document).ready(function(){
    var $uploadZone = $('div#fileupload');

    $uploadZone.fileupload({
        url: rlPlugins +'banners/upload/admin.php',
        maxNumberOfFiles: photo_allowed,
        maxFileSize: photo_client_max_size,
        acceptFileTypes: /^image\/(gif|jpe?g|png)$/,
        autoUpload: true
    }).on('fileuploaddone', function (e, data) {
        data.form.find('span.draft').hide();
    }).on('fileuploaddestroy', function () {
        $('span.draft').show();
    }).on('fileuploadfail', function () {
        printMessage('error', lang.error_upload_banner);
        $uploadZone.find('span.files').html('');
    });

    if (typeSelector.val() === 'image') {
        $.getJSON(rlPlugins +'banners/upload/admin.php', function (files) {
            $uploadZone.fileupload('option', 'done').call($uploadZone, null, {result: files});
        });
    }
});
{/literal}
</script>

<script type="text/javascript" src="{$smarty.const.RL_LIBS_URL}upload/tmpl.min.js"></script>
<script type="text/javascript" src="{$smarty.const.RL_LIBS_URL}upload/jquery.ui.widget.js"></script>
<script type="text/javascript" src="{$smarty.const.RL_LIBS_URL}upload/jquery.iframe-transport.js"></script>
<script type="text/javascript" src="{$smarty.const.RL_LIBS_URL}upload/jquery.fileupload.js"></script>
<script type="text/javascript" src="{$smarty.const.RL_LIBS_URL}upload/jquery.fileupload-ui.js"></script>
<script type="text/javascript" src="{$smarty.const.RL_LIBS_URL}upload/exif.js"></script>

<!-- banners upload manager end -->
