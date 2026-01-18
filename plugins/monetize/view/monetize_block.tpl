<!-- bumpup listings history tpl -->

<script>var listings_map = new Array();</script>
<div class="monetize-block">
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'featured.tpl'}
</div>
<script class="fl-js-dynamic">
{literal}
$(document).ready(function () {
    monetizer.hideData('.monetize-block');
});
{/literal}
</script>

<!-- bumpup listings history tpl end -->
