<!-- shoppingCart -->
<script class="fl-js-dynamic">
{literal}
$(document).ready(function(){
    $('.renew-auction').click(function() {
        shoppingCart.renewAuction($(this).attr('id').split('-')[1]);
    });
    $('.close-auction').on('click', function() {
        var id = $(this).attr('id').split('-')[1];

        $(this).flModal({
            type: 'notice',
            content: lang['shc_do_you_want_close_auction'],
            prompt: 'shoppingCart.closeAuction(' + id + ')',
            width: 'auto',
            height: 'auto',
            click: false
        });
    });
    $('.nav-icon > svg.icon').click(function() {
        window.location.href = $(this).next('a.shc-my-listings-link').attr('href');
    });
});
{/literal}
</script>
<!-- end shoppingCart -->
