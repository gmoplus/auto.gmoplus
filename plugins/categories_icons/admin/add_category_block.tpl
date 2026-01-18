<tr>
    <td class="name">{$lang.category_icon}</td>
    <td class="field category-icon-cont">
        {if $sPost.icon && $sPost.icon|strpos:'.svg' !== false}
            {assign var='category_is_svg' value=true}
        {/if}

        {if $config.rl_version|version_compare:'4.9.2':'>='}
            {assign var='icon_dir' value=$smarty.const.RL_LIBS_URL|cat:'icons/svg-line-set/'}

            <input type="hidden" name="category_icon_svg" value="{if $category_is_svg}{$sPost.icon}{/if}" />

            <span class="svg-icon-row{if $sPost.icon && !$category_is_svg} hide{/if}">
                {if $category_is_svg}
                    <img class="img-preview" style="margin-left: 0;" src="{$icon_dir|cat:$sPost.icon}" />
                {/if}

                <a class="icon-set" href="javascript://">{$lang.category_icon_choose_from_gallery}</a>

                <span class="icon-reset-cont hide">
                    / <a class="icon-reset" href="javascript://">{$lang.reset}</a>
                </span>
            </span>

            <span class="common-interface{if $category_is_svg} hide{/if}" style="margin: 0 10px;">{$lang.or}</span>
        {/if}

        <span class="common-interface{if $category_is_svg} hide{/if}">
            <input class="file" type="file" name="icon" />

            <span style="display: block;margin: 10px 0;" class="field_description">
                {assign var='width_replace' value=`$smarty.ldelim`width`$smarty.rdelim`}
                {assign var='height_replace' value=`$smarty.ldelim`height`$smarty.rdelim`}
                {assign var='click_replace' value='<a href="'|cat:$rlBase|cat:'index.php?controller=settings&group='|cat:$ci_groupID|cat:'">$1</a>'}
                {$lang.category_icon_notice|replace:$width_replace:$config.categories_icons_width|replace:$height_replace:$config.categories_icons_height|regex_replace:"/\[(.*)\]/":$click_replace}
            </span>
        </span>

        {if $sPost.icon && !$category_is_svg}
            <div id="gallery">
                <div style="margin: 1px 0 4px 0;">
                    <fieldset style="margin: 0 0 10px 0;">
                        <legend id="legend_details" class="up" onclick="fieldset_action('details');">{$lang.current_icon}</legend>
                        <div id="fileupload" class="ui-widget">
                            <span class="item active template-download" style="width: {math equation="x + y" x=$config.categories_icons_width y=8}px; height: {math equation="x + y" x=$config.categories_icons_width y=4}px;">
                                <img src="{$smarty.const.RL_FILES_URL}{$sPost.icon}" style="border: 2px solid #D0D0D0; border-radius: 5px 5px 5px 5px; display: block; height: {math equation="x + y" x=$config.categories_icons_width y=4}; width: {math equation="x + y" x=$config.categories_icons_width y=4}px;" alt="{$lang.category_icon}" />
                                <img title="Delete" alt="Delete" class="delete" src="{$rlTplBase}/img/blank.gif" onclick="xajax_deleteIcon('{$sPost.key}', '{if $smarty.get.controller == 'listing_types'}listing_type{else}category{/if}');" />
                            </span>
                        </div>
                    </fieldset>
                </div>
            </div>
            <div class="loading" id="photos_loading" style="width: 100%;"></div>
        {/if}
    </td>
</tr>

{if $config.rl_version|version_compare:'4.9.2':'>='}
<script>
lang['search'] = "{$lang.search}";
lang['choose'] = "{$lang.choose}";
lang['cancel'] = "{$lang.cancel}";
lang['load_more'] = "{$lang.load_more}";
{literal}

$(function(){
    var icons_dir = {/literal}'{$icon_dir}'{literal};
    var $svg_cont = $('.category-icon-cont .svg-icon-row');
    var $manage   = $svg_cont.find('a.icon-set');
    var $reset    = $svg_cont.find('a.icon-reset');
    var $input    = $('input[type=hidden][name=category_icon_svg]');
    var $commonInterface = $('.common-interface');

    $manage.flModal({
        width: 789,
        height: 'auto',
        caption: lang['svg_icon_file'],
        content: '<div class=""><input name="icon-search" type="text" placeholder="' + lang['search'] + '" /><div id="icons-manager-grid"><div>' + lang['loading'] + '</div></div><div class="icons-manager-next invisible"><input type="button" value="' + lang['load_more'] + '" /></div><div class="icons-manager-controls"><a class="cancel" href="javascript://">' + lang['cancel'] + '</a><input disabled="disabled" type="button" value="' + lang['choose'] + '" /></div></div>',
        onReady: function(){
            var $grid = $('#icons-manager-grid > div');
            var $controls = $('.icons-manager-controls');
            var $next_cont = $('.icons-manager-next');
            var $search = $('input[name=icon-search]');
            var $next = $next_cont.find('input');
            var $choose = $controls.find('input');

            var stack = 0;
            var search_timer = 0;
            var search_query = '';
            var closeWindow = function(){
                $('.modal-window > div:first > span:last').trigger('click');
            }

            $grid.on('click', '.icons-manager-grid-icon', function(){
                $('#icons-manager-grid').find('.icon-active').removeClass('icon-active');
                $(this).addClass('icon-active');
                $choose.attr('disabled', false);
            });
            $controls.find('.cancel').click(function(){
                closeWindow();
            });
            $choose.click(function(){
                var $active_icon = $grid.find('.icon-active');

                if (!$active_icon.data('name')) {
                    return;
                }

                $input.val($active_icon.data('name'));

                if ($svg_cont.find('img.img-preview').length) {
                    $svg_cont.find('img.img-preview').attr(
                        'src',
                        $active_icon.find('img').attr('src')
                    );
                } else {
                    $svg_cont.find('a.icon-set').before(
                        $('<img>')
                            .attr('src', $active_icon.find('img').attr('src'))
                            .addClass('img-preview')
                            .css('margin-left', '0')
                    );

                    $commonInterface.addClass('hide');
                }

                closeWindow();
            });

            $next.click(function(){
                stack++;
                loadStack();
                $next.val(lang['loading']);
            });

            $search.on('keyup', function(){
                clearTimeout(search_timer);

                stack = 0;
                search_query = $search.val().length < 3 ? '' : $search.val();

                search_timer = setTimeout(function(){
                    loadStack();
                }, 700);
            });

            var loadStack = function(){
                var data = {
                    start: stack,
                    q: search_query
                };
                flynax.sendAjaxRequest('getSVGIcons', data, function(response){
                    if (!stack) {
                        $grid.empty();
                    }

                    if (response == 'session_expired') {
                        location.reload();
                    } else if (response.results) {
                        $.each(response.results, function(index, icon_name){
                            if (/\.svg$/.test(icon_name)) {
                                var src = icons_dir + icon_name;
                                var class_name = $input.val() == icon_name ? 'icon-active' : '';
                                var $icon = '<div class="icons-manager-grid-icon ' + class_name + '" data-name="' + icon_name + '" title="' + icon_name.replace('.svg', '') + '"><img src="' + src + '" /></div>';
                                $grid.append($icon);
                            }
                        });

                        $next_cont[
                            response.next ? 'removeClass' : 'addClass'
                        ]('invisible');

                        if (stack) {
                            $next.val(lang['load_more']);
                            $grid.parent().animate({scrollTop: $grid.height()}, 'slow');
                        }
                    } else {
                        $grid.append(lang['system_error']);
                    }
                });
            };

            loadStack();
        }
    });

    $reset.click(function(){
        $input.val('');
        $svg_cont.find('img.img-preview').remove();
        $commonInterface.removeClass('hide');
    });
});

{/literal}
</script>
{/if}
