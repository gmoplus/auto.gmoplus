<!-- Shopping Cart Plugin -->

<script class="fl-js-dynamic">
    var currencyConverterAvailable  = '{$currencyConverter}';
    {literal}

    $(document).ready(function() {
        shoppingCartBasic.init();

        if (typeof currencyConverter !== 'undefined' && currencyConverterAvailable) {
            $('.shc_price').convertPrice();
        }

        {/literal}{if $config.shc_method == 'multi' && $pageInfo.Controller == 'my_shopping_cart' && $pageInfo.Controller == 'auction_payment'}{literal}
        if ($('.my_paygc_credits').length){
            $('.my_paygc_credits').remove();
        }
        {/literal}{/if}{literal}
    });
    
    {/literal}
</script>

<!-- end Shopping Cart Plugin -->
