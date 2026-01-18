
{include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'} 
<table class="list">
<tr>
    <td class="name">{$lang.total_listings}:</td>
    <td class="value">{$shcMigrateFields.total_listings}</td>
</tr>
<tr>
    <td class="name">{$lang.total_accounts}:</td>
    <td class="value">{$shcMigrateFields.total_accounts}</td>
</tr>
<tr>
    <td class="name">{$lang.total}:</td>
    <td class="value">{$shcMigrateFields.total}</td>
</tr>
<tr id="migrate_start_nav">
    <td style="height: 50px;min-width: 200px;">
        <span class="purple_13">&larr; </span><a class="cancel" href="{$rlBaseC}" style="padding: 0;">{$lang.cancel}</a>
    </td>
    <td class="value">
        <input id="start_migrate" type="button" value="{$lang.shc_migrate}" />
    </td>
</tr>
</table>

{include file=$smarty.const.RL_PLUGINS|cat:'shoppingCart/admin/view/migrate_interface.tpl'}

<script>
    {literal}
    var migrate_in_progress = false;

    $(document).ready(function(){
        $('#start_migrate').click(function(){
            migrateFields.start();
            $('#start_migrate').fadeOut();
        });
        $(window).bind('beforeunload', function() {
            if (migrate_in_progress) {
                return lang['shc_before_migrate_hint'];
            }
        });
    });

    var migrateFieldsClass = function(){
        var self = this;
        var item_width = width = percent = percent_value = 0;
        var shcPopupWindow = false;
        var request;

        this.phrases = {'completed': "{/literal}{$lang.shc_migrate_completed}{literal}"};

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
            request = $.getJSON(rlConfig["ajax_url"], {item: 'shoppingCartMigrateDate', index: index}, function(response){
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
                    $('#migrate_start_nav').slideUp();
                    printMessage('notice', self.phrases['completed'].replace('{count}', response['count']));
                }
            });
        }

        this.stop = function(){
            migrate_in_progress = false;
            request.abort();
        }

        this.start = function(){
            migrate_in_progress = true;
            self.update(0);
        }
    };

    var migrateFields = new migrateFieldsClass();
    {/literal}
</script>
{include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'} 
