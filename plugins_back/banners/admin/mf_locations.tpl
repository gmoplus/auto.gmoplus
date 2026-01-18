<!-- mf_locations.tpl -->

<ul {if $first}class="first"{/if}>
{foreach from=$mf_locations item='mf_entry' name='mflocName'}
    <li id="mf_tree_{$mf_entry.Key}">
        <img src="{$rlTplBase}img/blank.gif" alt="" />
        <label><input type="checkbox" name="mf_locations[]" value="{$mf_entry.Key}" /> <span>{$mf_entry.name}</span></label>
        <span class="tree_loader" style="padding:7px;"></span>
    </li>
{/foreach}
</ul>

<!-- mf_locations.tpl end -->
