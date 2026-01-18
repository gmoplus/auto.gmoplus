<!-- user authorization form -->

<div class="auth">{strip}
    <div class="cell">
        <div>
            <div class="caption">{$lang.sign_in}</div>

            <div class="name">{if $config.account_login_mode == 'email'}{$lang.mail}{else}{$lang.username}{/if}</div>
            <input class="w210" type="text" name="login[username]" maxlength="25" value="{$smarty.post.login.username}" />

            <div class="name">{$lang.password}</div>
            <input class="w210" type="password" name="login[password]" maxlength="25" />

            <div style="padding-top: 15px;"><a target="_blank" title="{$lang.remind_pass}" href="{$rlBase}{if $config.mod_rewrite}{$pages.remind}.html{else}?page={$pages.remind}{/if}">{$lang.forgot_pass}</a></div>
        </div>
    </div>
    <div class="divider">{$lang.or}</div>
    <div class="cell">
        <div>
            <div class="caption">{$lang.sign_up}</div>

            {if $quick_types && $quick_types|@count <= 1}
                <div class="name">{$lang.your_name}</div>
                <input class="w210" type="text" name="register[name]" maxlength="100" value="{$smarty.post.register.name}" />
            {/if}

            <div class="name">{$lang.your_email}</div>
            <input class="w210" type="text" name="register[email]" maxlength="150" value="{$smarty.post.register.email}"  />

            {rlHook name='shoppingCartQuickRegTpl'}

            {if $quick_types && $quick_types|@count > 1}
                <div class="name">{$lang.account_type}</div>
                <select class="w210" name="register[type]">
                    {foreach from=$quick_types item='quick_reg_type'}
                        <option value="{$quick_reg_type.ID}" {if $smarty.post.register.type == $quick_reg_type.ID}selected="selected"{/if}>{$quick_reg_type.name}</option>
                    {/foreach}
                </select>

                {foreach from=$quick_types item='quick_reg_type' name='acName'}
                    {if $quick_reg_type.desc}
                        <div class="qtip_cont">{$quick_reg_type.desc}</div>
                        <img class="qtip {if !$smarty.foreach.acName.first}hide {/if}sc_{$quick_reg_type.ID}" src="{$rlTplBase}img/blank.gif" alt="" />
                    {/if}
                {/foreach}
            {elseif $quick_types && $quick_types|@count > 1}
                <input type="hidden" name="register[type]" value="{$quick_types.0.ID}" />
            {/if}

            <div class="name">{$lang.account_type}</div>
            <select class="w210" name="register[plan]">
                {foreach from=$quick_plans item='quick_reg_plan'}
                    <option value="{$quick_reg_plan.ID}" {if $smarty.post.register.plan == $quick_reg_plan.ID}selected="selected"{/if}>{$quick_reg_plan.name}</option>
                {/foreach}
            </select>
        </div>
    </div>
{/strip}</div>

<!-- user authorization form end -->
