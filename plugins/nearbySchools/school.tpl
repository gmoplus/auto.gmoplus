<div class="row">
    <div data-caption="{$lang.rating_label}">
        <span class='{$schools[school].badge} {$schools[school].background}'>{if $schools[school].rating}{$schools[school].rating}{else}0{/if}</span>
        {if $schools[school].isNumeric || empty($schools[school].rating)}  of 10 {/if}
        <a target="_blank" rel="nofollow"
        href="{$schools[school].overviewLink}">{$schools[school].name}</a></span>
    </div>
    <div data-caption="{$lang.grades_label}">{$schools[school].gradeRange}</div>
    <div data-caption="{$lang.distance_label}">{$schools[school].distance} {$lang.nbs_miles}</div>
</div>
