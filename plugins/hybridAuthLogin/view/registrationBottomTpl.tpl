{include file=$hybrid_configs.path.view|cat:'/iconsContainer.tpl'}

<script class="fl-js-dynamic">
    {literal}
    $(document).ready(function () {
        var $iconsContainer = $('.ha-icons-container.in-registration');
        if ($iconsContainer.length) {
            var $contentContainer = $('#content div.content-padding');

            $contentContainer.prepend($iconsContainer);
            $iconsContainer.removeClass('hide');
        }
    });
    {/literal}
</script>
