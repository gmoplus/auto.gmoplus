<!-- Show warning message if previous main bucket is down tpl -->

{if $rsWarningAboutDownServer}
    <script>{literal}
        $(function () {
            printMessage('alert', '{/literal}{$rsWarningAboutDownServer}{literal}');
        })
    {/literal}</script>
{/if}

<!-- Show warning message if previous main bucket is down tpl end -->
