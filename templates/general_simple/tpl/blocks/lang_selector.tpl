<!-- languages selector -->

{if $languages|@count > 1}
    <span class="circle" id="lang-selector">
        <span class="default" accesskey="{$smarty.const.RL_LANG_CODE|ucfirst}">
            <img class="lang" src="{$rlTplBase}img/blank.gif" style="background-image: url('{$rlTplBase}img/lang_flags/{$smarty.const.RL_LANG_CODE|lower}.gif');" alt=""> {$languages[$smarty.const.RL_LANG_CODE].name}
        </span>
        <span class="content hide">
            <ul class="lang-selector">
                {foreach from=$languages item='lang_item'}
                    {if $lang_item.Code|lower == $smarty.const.RL_LANG_CODE|lower}{continue}{/if}
                    <li>
                        <a data-code="{$lang_item.Code|lower}" title="{$lang_item.name}" href="{if $hreflang[$lang_item.Code]}{$hreflang[$lang_item.Code]}{else}{if $lang_url_home}{$lang_url_home}{else}{$smarty.const.RL_URL_HOME}{/if}{if $config.mod_rewrite}{$lang_item.dCode}{$pageLink|replace:'&':'&amp;'}{else}{$pageLink}language={$lang_item.Code}{/if}{/if}">
                            <img class="lang" src="{$rlTplBase}img/blank.gif" style="background: url('{$rlTplBase}img/lang_flags/{$lang_item.Code|lower}.gif') 0 0 no-repeat;" alt="">{$lang_item.name}</a>
                    </li>
                {/foreach}
            </ul>
        </span>
    </span>
{/if}

<!-- languages selector end -->
