<!-- short listing information -->

<script>var listings_map = [];</script>

<section id="listings" class="grid row {if $block.Side == 'left'}booking-order{/if}">
    {include file='blocks/listing.tpl'}
</section>

<!-- @todo 3.1.0 - Remove this when "compatible" will be more than 4.6.2 -->
{if $config.rl_version|version_compare:'4.6.2' <= 0}<script class="fl-js-static">flynaxTpl.hisrc();</script>{/if}

<!-- short listing information end -->
