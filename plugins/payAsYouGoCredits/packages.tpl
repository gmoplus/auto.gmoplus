<!-- payAsYouGoCredits plugin, packages -->

<ul id="credits">
    {foreach from=$credits item='item' key='key' name='creditsF'}
        <li class="credit_item" id="credit_item_{$item.ID}">
            <div class="name number font-weight-bold">{$item.name}</div>
            <div class="number">{$item.Credits}</div>
            <div class="credits">{$lang.paygc_credits_count}</div>
            <div class="price">{strip}
                {if $config.system_currency_position == 'before'}{$config.system_currency}&nbsp;{/if}
                {$item.Price}
                {if $config.system_currency_position == 'after'}&nbsp;{$config.system_currency}{/if}
            {/strip}</div>
            <div class="price_one">{strip}
                ({if $config.system_currency_position == 'before'}{$config.system_currency}&nbsp;{/if}
                {$item.Price_one}
                {if $config.system_currency_position == 'after'}&nbsp;{$config.system_currency}{/if}
                &nbsp;=&nbsp;{$lang.paygc_one_credit})
            {/strip}</div>
            <input type="radio" id="credit_item_value_{$item.ID}" accesskey="price_{$item.Price}" name="package_id" value="{$item.ID}" />
        </li>
    {/foreach}
</ul>

<script class="fl-js-dynamic">
{literal}
    $(function() {
        $('ul#credits li.credit_item').click(function() {
            // Set "active" status for the selected package
            $('ul#credits li.credit_item').removeClass('active');
            $(this).addClass('active');

            // Select the radio box for the selected package
            $('ul#credits li.credit_item input[name="package_id"]').prop('checked', false);
            $(this).find('input[name="package_id"]').prop('checked', true);
        })
    });
{/literal}
</script>

<!-- payAsYouGoCredits plugin, packages end -->
