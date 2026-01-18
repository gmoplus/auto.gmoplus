<!-- ccbill settings tpl -->

<form method="post" action="{$rlBase}index.php?controller={$cInfo.Controller}">
    {foreach from=$ccbill_groups item='group'}
    <table class="form">
        <tr>
            <td class="divider_line">
                <div class="inner">{$group.name}</div>
            </td>
        </tr>
        <tr>
            <td>
                <table class="form">
                    <tr>
                        <td align="center">{$lang.item}</td>
                        <td>{$lang.ccbill_form}</td>
                        <td>{$lang.ccbill_allowed_type}</td>
                    </tr>
                    {foreach from=$group.items item='item' name='groupF'}
                        <tr class="{if $smarty.foreach.groupF.iteration%2 != 0}highlight{/if}">
                            <td class="name" style="width: 210px;">{$item.name}</td>
                            <td class="field">
                                <input class="text" type="text" name="f[{$group.Key}][{$item.ID}][form]" value="{$item.form}" />
                            </td>
                            <td class="field">
                                <input class="text" type="text" name="f[{$group.Key}][{$item.ID}][allowed_types]" value="{$item.allowed_types}" />
                            </td>
                        </tr>
                    {/foreach}
                </table>
            </td>
        </tr>
    </table>
    {/foreach}
    <table class="form">
        <tr>
            <td></td>
            <td><input style="margin: 10px 0 0 0;" type="submit" class="button" value="{$lang.save}" /></td>
        </tr>
    </table>
</form>

<!-- end ccbill settings tpl -->

<!-- end ccbill settings tpl -->