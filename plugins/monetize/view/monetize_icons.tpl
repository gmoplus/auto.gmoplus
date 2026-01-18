<!-- monetize icons tpl -->
{if $bumpupPlans|@count > 0}
<li class="nav-icon bump-up">
    <a href="{pageUrl page='bumpup_page' vars='id='|cat:$listing.ID}"><span>{$lang.bumpup_listing}</span>&nbsp;</a>
</li>
{/if}
{if $highlightPlans|@count > 0}
<li class="nav-icon higlight">
    <a href="{pageUrl page='highlight_page' vars='id='|cat:$listing.ID}"><span>{$lang.m_highlight}</span>&nbsp;</a>
</li>
{/if}
<!-- monetize icons tpl end -->
