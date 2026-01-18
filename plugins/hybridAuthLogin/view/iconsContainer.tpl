{if $ha_networks_icons}
    <div class="ha-icons-container{if $icon_container_class} {$icon_container_class}{/if}{if $icon_container_class == 'in-registration'} hide{/if}">
        <div class="ha-or"><span>{$lang.or}</span></div>

        <div class="ha-social-icons">
            {foreach from=$ha_networks_icons item="icon"}
                <div class="ha-social-icon">
                    <a class="ha-{$icon.network}-provider {if $loginAttemptsLeft <= 0 && $config.security_login_attempt_user_module}ha-disabled{/if}" href="{$icon.url}">
                        <svg viewBox="0 0 24 24" class="ha-social-icon-svg">
                            <use xlink:href="#ga-{$icon.network}"></use>
                        </svg>
                    </a>
                </div>
            {/foreach}
        </div>
    </div>
{/if}
