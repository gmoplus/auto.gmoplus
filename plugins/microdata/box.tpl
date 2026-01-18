<!-- microdata added json code -->
{if $microdata}
    <script type="application/ld+json">
        {$microdata}
    </script>
{/if}

{if $microdataErrors}
<!-- Microdata errors
    {foreach from=$microdataErrors item='item'}
        {$item}
    {/foreach}
-->
{/if}
<!-- microdata added json code end -->
