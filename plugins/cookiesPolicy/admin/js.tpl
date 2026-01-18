<!-- cookiesPolicy javascript on settings page -->

<script>
{literal}

$(document).ready(function(){
    var $select = $('select[name="post_config[cookiesPolicy_view][value]"]');
    var onChangeView = function(){
        $('[name="post_config[cookiesPolicy_position][value]"],[name="post_config[cookiesPolicy_hide_icon][value]"],[name="post_config[cookiesPolicy_redirect_url][value]"]').closest('tr')[
            $select.val() == 'banner' ? 'hide' : 'show'
        ]();
    }

    onChangeView();

    $select.change(function(){
        onChangeView();
    });
});

{/literal}
</script>

<!-- cookiesPolicy javascript on settings page end -->
