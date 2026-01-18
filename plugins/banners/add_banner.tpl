<!-- add banner -->

{if !$no_access}
    <!-- steps -->
    {assign var='allow_link' value=true}
    {assign var='current_found' value=false}
    <ul class="steps">
        {math assign='step_width' equation='round(100/count, 3)' count=$bSteps|@count}
        {foreach from=$bSteps item='step' name='stepsF' key='step_key'}{strip}
            {if $curStep == $step_key || !$curStep}{assign var='allow_link' value=false}{/if}
            <li style="width: {$step_width}%;" class="{if $curStep}{if $curStep == $step_key}current{assign var='current_found' value=true}{elseif !$current_found}past{/if}{elseif $smarty.foreach.stepsF.first}current{/if}">
                <a title="{$step.name}"
                    href="{strip}
                        {if $allow_link}
                            {pageUrl key=$pageInfo.Key add_url="step=`$bSteps.$step_key.path`"}
                        {else}
                            javascript:void(0)
                        {/if}
                    {/strip}"
                >
                    {if $step.caption}<span>{$lang.step}</span> {$smarty.foreach.stepsF.iteration}{else}{$step.name}{/if}
                </a>
            </li>
        {/strip}{/foreach}
    </ul>
    <!-- steps end -->

    {assign var='sPost' value=$smarty.post}

    {if $curStep == 'plan'}
        <!-- select a plan -->
        <h1>{$lang.select_plan}</h1>

        <div class="area_plan step_area hide">
            <form method="post" action="{pageUrl key=$pageInfo.Key add_url="step=`$bSteps.$curStep.path`"}">
                <input type="hidden" name="step" value="plan" />

                {include file=$smarty.const.RL_PLUGINS|cat:'banners/banner_plans.tpl'}

                <div class="form-buttons">
                    <input type="submit" value="{$lang.next_step}" />
                </div>
            </form>
        </div>
        <!-- select a plan end -->
    {elseif $curStep == 'form'}
        <h1>{$lang.fill_out_form}</h1>

        <div class="area_form step_area hide">
            <form id="banners-form" method="post" action="{pageUrl key=$pageInfo.Key add_url="step=`$bSteps.$curStep.path`"}">
                <input type="hidden" name="step" value="form" />
                <input type="hidden" name="fromPost" value="1" />

                <div class="content-padding">
                    <!-- fields block -->
                    {strip}

                    <div class="submit-cell">
                        <div class="name">
                            {$lang.name} <span class="red">*</span>
                        </div>

                        <div class="field single-field" id="sf_field_name">
                            {if $languages|@count > 1}
                                <ul class="tabs tabs-hash">
                                    {foreach from=$languages item='language' name='langF'}
                                        <li lang="{$language.Code}" {if $smarty.foreach.langF.first}class="active"{/if}>
                                            <a href="#name_{$language.Code}" data-target="name_{$language.Code}">
                                                {$language.name}
                                            </a>
                                        </li>
                                    {/foreach}
                                </ul>

                                <div class="ml_tabs_content light-inputs">
                                    {foreach from=$languages item='language' name='langF'}
                                    {assign var='l_code' value=$language.Code}
                                    <div lang="{$l_code}"
                                        {if !$smarty.foreach.langF.first}class="hide"{/if}
                                        id="area_name_{$l_code}">
                                        <input type="text" name="name[{$l_code}]]" maxlength="255" value="{$sPost.name.$l_code}" />
                                    </div>
                                    {/foreach}
                                </div>
                            {else}
                                <input type="text" name="name" maxlength="255" value="{$sPost.name}" />
                            {/if}
                        </div>
                    </div>

                    <div class="submit-cell">
                        <div class="name">
                            {$lang.banners_bannerBox} <span class="red">*</span>
                        </div>

                        <div class="field single-field" id="sf_field_banner_box">
                            <select name="banner_box">
                            {foreach from=$planInfo.boxes item='box' name='fBox'}
                                {if $sPost.banner_box == $box.Key}
                                    {assign var='sBox' value=$box}
                                {else}
                                    {if $smarty.foreach.fBox.first}
                                        {assign var='sBox' value=$box}
                                    {/if}
                                {/if}
                                <option {if $sBox.Key == $box.Key}selected="selected"{/if} value="{$box.Key}" info="{$box.side}:{$box.width}:{$box.height}">
                                    {if $box.name}{$box.name}{else}{phrase key='blocks+name+'|cat:$box.Key db_check=true}{/if}
                                </option>
                            {/foreach}
                            </select>
                        </div>
                    </div>

                    <div class="submit-cell">
                        <div class="name">
                            {$lang.banners_bannerType} <span class="red">*</span>
                        </div>

                        <div class="field single-field" id="sf_field_banner_type">
                            <select name="banner_type">
                                {foreach from=$planInfo.types item='type'}
                                    <option {if $sPost.banner_type == $type.Key}selected="selected"{/if} value="{$type.Key}">{$type.name}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>

                    <div class="submit-cell {if !$sPost.banner_type || $sPost.banner_type != 'html'}hide{/if}" id="html_responsive">
                        <div class="name">
                            {$lang.banners_html_responsive}
                        </div>

                        <div class="field inline-fields" id="sf_field_banner_html_responsive">
                            <span class="custom-input">
                                <label><input type="radio" value="1" name="responsive" {if $sPost.responsive}checked="checked" {/if}/>{$lang.yes}</label>
                            </span>
                            <span class="custom-input">
                                <label><input type="radio" value="0" name="responsive" {if !$sPost.responsive}checked="checked" {/if}/>{$lang.no}</label>
                            </span>
                        </div>
                    </div>

                    <div class="submit-cell {if $sPost.banner_type && $sPost.banner_type != 'image'}hide{/if}" id="b_link">
                        <div class="name">
                            {$lang.banners_bannerLink}
                        </div>

                        <div class="field single-field" id="sf_field_banner_link">
                            <input type="text" name="link" value="{$sPost.link}" />
                        </div>
                    </div>

                    <div class="submit-cell {if $sPost.banner_type != 'html'}hide{/if}" id="btype_html">
                        <div class="name">
                            {$lang.banners_bannerType_html} <span class="red">*</span>
                        </div>

                        <div class="field single-field" id="sf_field_banner_link">
                            <textarea id="banner_html" name="html" rows="3" cols="">{$sPost.html}</textarea>
                        </div>
                    </div>

                    {/strip}
                    <!-- fields block end -->

                    <div class="submit-cell form-buttons">
                        <div class="name">
                            <a href="{pageUrl key=$pageInfo.Key add_url="step=`$bSteps.plan.path`"}">
                                {if $smarty.const.RL_LANG_DIR == 'ltr'}&larr;{else}&rarr;{/if} {$lang.perv_step}
                            </a>
                        </div>
                        <div>
                            <input type="submit" value="{$lang.next_step}" id="form_submit" />
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <script class="fl-js-dynamic">
        var _html_selectors = 'btype_html, #banners-form div#html_responsive';
        {literal}

        function bannerTypeChange(from, to, step) {
            $('#banners-form div#'+ from).fadeOut('fast', function() {
                $('#banners-form div#'+ to).fadeIn('normal');
            });

            if ( step ) {
                $('#step_media').fadeIn('fast')
            }
            else {
                $('#step_media').fadeOut('fast')
            }
        }

        if ($('select[name=banner_type]').val() == 'html') {
            bannerTypeChange('b_link', _html_selectors, 0);
        }

        $(document).ready(function() {
            $('select[name=banner_type]').change(function() {
                if ( $(this).val() == 'html' ) {
                    bannerTypeChange('b_link', _html_selectors, 0);
                }
                else {
                    bannerTypeChange(_html_selectors, 'b_link', 1);
                }
            });
        });
        {/literal}</script>
    {elseif $curStep == 'media'}
        <h1>{$lang.banners_addBannerContent}</h1>

        <!-- upload -->
        <div class="area_media step_area hide content-padding">
            {if $boxInfo.type == 'image'}
                <input type="hidden" name="banner_type" value="image" />
                {include file=$smarty.const.RL_PLUGINS|cat:'banners/upload/account_manager.tpl'}
            {/if}

            <form method="post"
                onsubmit="return submit_photo_step('{$boxInfo.type}');"
                action="{pageUrl key=$pageInfo.Key add_url="step=`$bSteps.$curStep.path`"}"
                enctype="multipart/form-data"
            >
                <input type="hidden" name="step" value="media" />
                <input type="hidden" name="type" value="{$boxInfo.type}" />

                <div class="form-buttons">
                    <a href="{pageUrl key=$pageInfo.Key add_url="step=`$prev_step.path`"}">
                        {if $smarty.const.RL_LANG_DIR == 'ltr'}&larr;{else}&rarr;{/if} {$lang.perv_step}
                    </a>
                    <input type="submit" value="{$lang.next_step}" id="photo_submit" />
                </div>
            </form>
        </div>
        <!-- upload end -->
    {elseif $curStep == 'checkout'}
        <h1>{$lang.checkout}</h1>

        <!-- checkout -->
        <div class="area_checkout step_area hide content-padding">
            {if isset($smarty.get.canceled)}
                <script class="fl-js-dynamic">
                    printMessage('error', '{$lang.bannersNoticePaymentCanceled}', 0, 1);
                </script>
            {/if}

            {assign var='showFormButtons' value=true}
            {if $txn_info && $txn_info.Txn_ID != ''}
                {assign var='showFormButtons' value=false}
            {/if}

            {if $showFormButtons}
                <div style="padding-bottom: 5px;">{$lang.checkout_step_info}</div>
            {/if}

            <form method="post" id="form-checkout" action="{pageUrl key=$pageInfo.Key add_url="step=`$bSteps.$curStep.path`"}">
                <input type="hidden" name="step" value="checkout" />

                {gateways}

                {if $showFormButtons}
                    <div class="form-buttons">
                        <a href="{pageUrl key=$pageInfo.Key add_url="step=`$prev_step.path`"}">
                            {if $smarty.const.RL_LANG_DIR == 'ltr'}&larr;{else}&rarr;{/if} {$lang.perv_step}
                        </a>
                        <input type="submit" value="{$lang.next_step}" id="checkout_submit" />
                    </div>
                {/if}
            </form>

            <script class="fl-js-dynamic">
                flynax.paymentGateway();
            </script>
        </div>
        <!-- checkout end -->
    {elseif $curStep == 'done'}
        <!-- done -->
        <div class="area_done step_area hide">
            <div class="text-notice">
                {if $config.banners_auto_approval}
                    {$lang.banners_noticeAfterBannerAdding}
                {else}
                    {$lang.banners_noticeAfterBannerAddingPending}
                {/if}
            </div>

            {assign var='replace' value='<a href="'|cat:$returnLink|cat:'">$1</a>'}
            {$lang.banners_addOneMoreBanner|regex_replace:'/\[(.*)\]/':$replace}
        </div>
        <!-- done end -->
    {/if}

    <script class="fl-js-dynamic">
    {if $curStep}
        flynax.switchStep('{$curStep}');
    {/if}
    </script>
{/if}

<!-- add banner end -->
