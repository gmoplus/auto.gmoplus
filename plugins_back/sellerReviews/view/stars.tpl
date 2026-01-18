<!-- SellerReviews stars controller tpl -->

{if $srrStarBig}
    {assign var='starClass' value='srr-star-add srr-star-add_active'}
{else}
    {assign var='starClass' value='srr-star-page'}
{/if}

<div class="d-flex {$starsContainerClass}">
    {section name='stars' start=1 loop=$srrRating+1}
        {assign var='i' value=$smarty.section.stars.iteration}
        <span class="srr-star {$starClass} {if $srrActiveStars}{if $srrActiveStars < $i}inactive{/if}{/if}"></span>
    {/section}

    {* Put next star with partially filled if rating have fractional part of a number *}
    {if intval($srrRating) != $srrRating}
        {assign var='decimalPieces' value='.'|explode:$srrRating}
        <span class="srr-star {$starClass} inactive"><span style="width: {$decimalPieces.1}0%;"></span></span>
    {/if}
</div>

<!-- SellerReviews stars controller tpl end -->
