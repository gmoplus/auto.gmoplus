<!-- shopping cart option -->

<script type="text/javascript"> 
{literal}
$(document).ready(function(){
    $('#shc-group ul.tabs>li').click(function() {
        shoppingCart.priceFormatTabs($(this).attr('lang'));
    });
});
{/literal}
</script>

<!-- shopping cart option end -->