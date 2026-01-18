<!-- varify photos page -->

{addCSS file=$smarty.const.RL_PLUGINS_URL|cat:'listing_status/static/style-page.css'}

{if $verified_data && $verified_data.pending}
    {assign var='isFancyappsExist' value=false}
    {if file_exists($smarty.const.RL_LIBS|cat:'fancyapps/fancybox.umd.js')}
        {assign var='isFancyappsExist' value=true}
    {/if}

    <div class="content-padding verify-photo-pending">
        <div class="table-cell clearfix">
            <div class="name" title="{$lang.listing}"><div><span>{$lang.listing}</span></div></div>
            <div class="value">
                <a href="{$listing.url}">{$listing.listing_title}</a>
            </div>
        </div>

        <div class="table-cell clearfix">
            <div class="name" title="{$lang.status}"><div><span>{$lang.status}</span></div></div>
            <div class="value">
                <div class="statuses">
                    <span class="pending">{$lang.ls_pending_verification}</span>
                </div>
            </div>
        </div>

        <div class="table-cell clearfix">
            <div class="name" title="{$lang.date}"><div><span>{$lang.date}</span></div></div>
            <div class="value">
                {$verified_data.Date|date_format:$smarty.const.RL_DATE_FORMAT}
            </div>
        </div>

        <div class="table-cell clearfix">
            <div class="name" title="{$lang.photo}"><div><span>{$lang.photo}</span></div></div>
            <div class="value">
                <div class="verify-photo-cont">
                    <a href="{$smarty.const.RL_FILES_URL}verified_photos/{$verified_data.Image}" data-fancybox="gallery">
                        <img class="request-photo" src="{$smarty.const.RL_FILES_URL}verified_photos/{$verified_data.Image}" />
                    </a>

                    <script class="fl-js-dynamic">
                        let is_fancy_app = {if $isFancyappsExist}true{else}false{/if};
                        {literal}
                            $(function(){
                                if (is_fancy_app) {
                                    flUtil.loadStyle(rlConfig.libs_url + 'fancyapps/fancybox.css');
                                    flUtil.loadScript(rlConfig.libs_url + 'fancyapps/fancybox.umd.js', function(){
                                        Fancybox.bind("[data-fancybox]", {
                                        });
                                    });
                                }
                                else {
                                    flUtil.loadStyle(rlConfig.libs_url + 'jquery/fancybox/jquery.fancybox.css');
                                    flUtil.loadScript(rlConfig.libs_url + 'jquery/jquery.fancybox.js', function(){
                                        $("[data-fancybox]").fancybox();
                                    });
                                }
                            });
                        {/literal}
                    </script>
                </div>
            </div>
        </div>

        <div class="table-cell clearfix">
            <div class="name" title="{$lang.description}"><div><span>{$lang.description}</span></div></div>
            <div class="value">
                {if $verified_data.Description}{$verified_data.Description}{else}{$lang.not_available}{/if}
            </div>
        </div>
    </div>
{else}
    <div class="verified-photos content-padding">
        
        <form action="{pageUrl key=$pageInfo.Key vars='id='|cat:$smarty.get.id}" method="post" enctype="multipart/form-data" >
            <input type="hidden" name="submit" value="1"/>

            <p  class="pb-3">
                {$lang.ls_verified_desc}
            </p>

            <div class="submit-cell">
                <div class="field">
                    <textarea rows="5" cols="8" name="description" placeholder="{$lang.description}">{$verified_data.Description}</textarea>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-6 mb-3">
                    <div class="file-input">
                        <input type="hidden" name="image_file" value="" />
                        <input class="file" type="file" name="image" />
                        <input type="text" class="file-name" name="" placeholder="* {$lang.ls_image}" />
                        <span>{$lang.choose}</span>
                    </div>
                </div>
                <div class="col-sm-6 verified-photos__send">
                    <input type="submit" value="{$lang.send}" />
                </div>
            </div>
        </form>
    </div>

    <script class="fl-js-dynamic">
    {literal}

    $(function(){
        "use strict";

        $('.file-input input[type=file]')
            .unbind('change')
            .bind('change', function(){
                var path = $(this).val().split('\\');
                $(this).parent().find('input[type=text]')
                    .removeClass('error')
                    .val(path[path.length - 1]);
            });
    });

    {/literal}
</script>
{/if}

<!-- varify photos page end -->
