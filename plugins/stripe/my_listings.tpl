<!-- stripe plugin -->

<script type="text/javascript">
{literal}

$(document).ready(function() {
    $('.stripe-unsubscription').each(function() {
        $(this).flModal({
            caption: '',
            content: '{/literal}{$lang.stripe_unsubscripbe_confirmation}{literal}',
            prompt: 'xajax_cancelSubscription(\''+ $(this).attr('id').split('-')[0] +'\', '+ $(this).attr('id').split('-')[1] +')',
            width: 'auto',
            height: 'auto'
        });
    });                     
});

{/literal}
</script>

<!-- end stripe plugin -->