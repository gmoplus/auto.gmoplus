
{include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'} 
{if $smarty.get.action == 'apply'}
    <table class="list">
    <tr>
        <td class="name">{$lang.total_listings}:</td>
        <td class="value{if $smarty.foreach.itemF.first} first{/if}">{$updateListings.total}</td>
    </tr>
    <tr id="update_start_nav">
        <td style="height: 50px;min-width: 200px;">
            <span class="purple_13">&larr; </span><a class="cancel" href="{$rlBaseC}" style="padding: 0;">{$lang.cancel}</a>
        </td>
        <td class="value">
            <input id="start_update" type="button" value="{$lang.update}" />
        </td>
    </tr>
    </table>

    {include file=$smarty.const.RL_PLUGINS|cat:'shoppingCart/admin/view/update_interface.tpl'}

    <script>
        {literal}
        var update_in_progress = false;

        $(document).ready(function(){
            $('#start_update').click(function(){
                updateListings.start();
                $('#start_update').fadeOut();
            });
            $(window).bind('beforeunload', function() {
                if (update_in_progress) {
                    return lang['shc_before_update_hint'];
                }
            });
        });

        var updateListingsClass = function(){
            var self = this;
            var item_width = width = percent = percent_value = 0;
            var shcPopupWindow = false;
            var request;

            this.phrases = {'completed': "{/literal}{$lang.shc_update_completed}{literal}"};

            this.update = function(index){
                /* show window */
                if (index == 0) {
                    if (!shcPopupWindow) {
                        shcPopupWindow = new Ext.Window({
                            applyTo: 'statistic',
                            layout: 'fit',
                            width: 447,
                            height: 120,
                            closeAction: 'hide',
                            plain: true
                        });

                        shcPopupWindow.addListener('hide', function(){
                            self.stop();
                        });
                    }

                    shcPopupWindow.show();
                }

                /* update request */
                request = $.getJSON(rlConfig["ajax_url"], {item: 'shoppingCartUpdateListings', index: index}, function(response){
                    index = response['from'];
                    var percent = Math.ceil((response['from'] * 100) / response['count']);
                    percent = percent > 100 ? 100 : percent;

                    $('#processing').css('width', percent+'%');
                    $('#loading_percent').html(percent+'%');

                    if (response['count'] > index) {
                        var from = response['from'] + 1;
                        var to = response['to'] + 1;
                        to = response['count'] < to ? response['count'] : to;
                        var update_current = from+'-'+to;
                        $('#updateing').html(update_current);

                        self.update(index);
                    } else {
                        shcPopupWindow.hide();
                        $('#update_start_nav').slideUp();
                        printMessage('notice', self.phrases['completed'].replace('{count}', response['count']));
                    }
                });
            }

            this.stop = function(){
                update_in_progress = false;
                request.abort();
            }

            this.start = function(){
                update_in_progress = true;
                self.update(0);
            }
        };

        var updateListings = new updateListingsClass();
        {/literal}
    </script>
{else}
    <form id="shc-update-listings" action="{$rlBaseC}module=update_listings" method="post">
    <table class="form">
        <tr>
            <td class="name">{$lang.listing_type}</td>
            <td class="field">
                {foreach from=$listing_types item='l_type'}
                    <div class="option_padding" id="lt_{$l_type.Key}">
                        {assign var='replace' value=`$smarty.ldelim`type`$smarty.rdelim`}
                        <div style="margin: 0 20px 0 0;">
                            <label><input type="checkbox" {if $l_type.Key|in_array:$smarty.post.types}checked="checked"{/if} name="types[]" class="l-type" value="{$l_type.Key}" />  {$l_type.name}</label>
                        </div>
                        <fieldset class="light{if !$l_type.Key|in_array:$smarty.post.types} hide{/if}" style="margin-top: 5px;">
                            <legend id="legend_settings_{$l_type.Key}" class="up" onclick="fieldset_action('settings_{$l_type.Key}');">{$lang.settings}</legend>
                            <div id="settings_{$l_type.Key}" class="settings">
                                {assign var='l_type_key' value=$l_type.Key}
                                <table class="form">
                                <tr>
                                    <td class="name">{$lang.shc_price_format}</td>
                                    <td class="field">
                                        <label><input class="shc-mode" type="radio" name="{$l_type.Key}[shc_mode]" {if $smarty.post.$l_type_key.shc_mode != 'auction'}checked="checked"{/if} data-type="{$l_type.Key}" value="fixed" /> {$lang.shc_mode_fixed}</label>
                                        <label><input class="shc-mode" type="radio" name="{$l_type.Key}[shc_mode]" {if $smarty.post.$l_type_key.shc_mode == 'auction'}checked="checked"{/if} data-type="{$l_type.Key}" value="auction" /> {$lang.shc_auction}</label>
                                    </td>
                                </tr>
                                <tr class="shc-item auction hide">
                                    <td class="name">
                                        {$lang.shc_start_price}
                                    </td>
                                    <td class="field" id="sf_field_shc_bid_step">
                                        <input class="numeric w50" type="text" style="width: 70px;" name="{$l_type.Key}[start_price]" maxlength="11" value="{if $smarty.post.$l_type_key.start_price}{$smarty.post.$l_type_key.start_price}{else}0{/if}" />&nbsp;<span class="shc-currency">% {$lang.shc_percent_from_price}</span>
                                    </td>
                                </tr>
                                <tr class="shc-item auction hide">
                                    <td class="name">
                                        {$lang.shc_bid_step}
                                    </td>
                                    <td class="field" id="sf_field_shc_bid_step">
                                        <input class="numeric w50" type="text" style="width: 70px;" name="{$l_type.Key}[bid_step]" maxlength="11" value="{$smarty.post.$l_type_key.bid_step}" />&nbsp;<span class="shc-currency"><span>{$config.system_currency}</span></span>
                                    </td>
                                </tr>
                                <tr class="shc-item auction hide">
                                    <td class="name">
                                        {$lang.shc_duration}
                                    </td>
                                    <td class="field" id="sf_field_shc_days">
                                        <input class="numeric w50" type="text" style="width: 70px;" name="{$l_type.Key}[days]" maxlength="11" value="{$smarty.post.$l_type_key.days}" />&nbsp;<span>{$lang.shc_days}</span>
                                    </td>
                                </tr>
                                <tr class="shc-item auction fixed quantity">
                                    <td class="name">
                                        {$lang.shc_quantity}
                                    </td>
                                    <td class="field" id="sf_field_shc_quantity">
                                        <input class="numeric w50" type="text" style="width: 70px;" name="{$l_type.Key}[quantity]" value="{if $smarty.post.$l_type_key.quantity}{$smarty.post.$l_type_key.quantity}{else}1{/if}" maxlength="11" />
                                    </td>
                                </tr>
                                </table>
                            </div>
                        </fieldset>
                    </div>
                {/foreach}
            </td>
        </tr>
        <tr>
            <td class="name">{$lang.account_type}</td>
            <td class="field">
                <div class="option_padding">
                    {foreach from=$account_types item='a_type'}
                        <div style="margin: 0 20px 0 0;">
                            <label><input type="checkbox" {if $a_type.Key|in_array:$smarty.post.atypes}checked="checked"{/if} name="atypes[]" value="{$a_type.Key}" />  {$a_type.name}</label>
                        </div>
                    {/foreach}
                </div>
            </td>
        </tr>
        <tr>
            <td></td>
            <td class="field">
                <input type="submit" value="{$lang.update}" />
            </td>
        </tr>
    </table>
    </form>
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'} 

    <script class="fl-js-dynamic">
        {literal}
        $(document).ready(function(){
            $('.l-type').click(function() {
                var type = $(this).val();
                if ($(this).is(':checked')) {
                    $('#lt_' + type).find('fieldset').removeClass('hide');
                } else {
                    $('#lt_' + type).find('fieldset').addClass('hide');
                }
            });
            $('.shc-mode').each(function() {
                var type = $(this).data('type');
                if (!$('#lt_' + type).find('fieldset').hasClass('hide')) {
                    controlPriceFormat($(this).val(), type);
                }
            });
            $('.shc-mode').click(function() {
                controlPriceFormat($(this).val(), $(this).data('type'));
            });
        });

        var controlPriceFormat = function(mode, type) {
            $('#settings_' + type + ' .shc-item').each(function() {
                if ($(this).hasClass(mode)) {
                    $(this).removeClass('hide');
                } else {
                    $(this).addClass('hide');
                }
            });
        }
    {/literal}
    </script>
{/if}
