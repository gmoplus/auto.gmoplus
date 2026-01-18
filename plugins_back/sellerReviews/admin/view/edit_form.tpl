<!-- SellerReviews edit comment form tpl -->

{assign var='sPost' value=$smarty.post}
{include file='blocks/m_block_start.tpl'}

<form action="{$rlBaseC}action=edit&id={$smarty.get.id}" method="post">
    <input type="hidden" name="submit" value="1" />

    <table class="form">
        <tr>
            <td class="name">{$lang.account}</td>
            <td class="field">
                <input type="hidden" name="account" value="{$sPost.account}" />
                <b>{$sPost.account}</b>
            </td>
        </tr>
        <tr>
            <td class="name">{$lang.srr_author}</td>
            <td class="field">
                <input type="hidden" name="author" value="{$sPost.author}" />
                <b>{$sPost.author}</b>
            </td>
        </tr>
        <tr>
            <td class="name"><span class="red">*</span>{$lang.srr_title}</td>
            <td class="field">
                <input value="{$smarty.post.title}" class="w350" name="title" type="text" size="100" />
            </td>
        </tr>
        <tr>
            <td class="name"><span class="red">*</span>{$lang.description}</td>
            <td class="field">
                <textarea name="description" rows="10" style="height: 150px;">{$smarty.post.description}</textarea>
            </td>
        </tr>
        <tr>
            <td class="name">{$lang.status}</td>
            <td class="field">
                <select name="status">
                    {foreach from=$srr_statuses item='status'}
                        <option value="{$status}" {if $sPost.status == $status}selected="selected"{/if}>
                            {$lang.$status}
                        </option>
                    {/foreach}
                </select>
            </td>
        </tr>
        <tr>
            <td></td>
            <td class="field">
                <input type="submit" value="{if $smarty.get.action == 'edit'}{$lang.edit}{else}{$lang.add}{/if}" />
            </td>
        </tr>
    </table>
</form>

{include file='blocks/m_block_end.tpl'}

<!-- SellerReviews edit comment form tpl end -->
