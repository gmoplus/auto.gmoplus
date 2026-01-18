<!-- monetize -->
<div id="monetize">
    <table class="form">
        <tbody>
            <tr>
                <td class="name">{$lang.bumpups}</td>
                <td class="field">
                    <select name="bumpup_id">
                        <option value="0">{$lang.select}</option>
                        {foreach from=$bumpupPlans item='plan'}
                            <option value="{$plan.ID}" {if $sPost.bumpup_id == $plan.ID}selected{/if}>{$plan.name}</option>
                        {/foreach}
                    </select>
                </td>
            </tr>
            <tr>
                <td class="name">{$lang.m_highlights}</td>
                <td class="field">
                    <select name="highlight_id">
                        <option value="0">{$lang.select}</option>
                        {foreach from=$highlightPlans item='plan'}
                            <option value="{$plan.ID}" {if $sPost.highlight_id == $plan.ID}selected{/if}>{$plan.name}</option>
                        {/foreach}
                    </select>
                </td>
            </tr>
        </tbody>
    </table>
</div>
<!-- monetize end -->
