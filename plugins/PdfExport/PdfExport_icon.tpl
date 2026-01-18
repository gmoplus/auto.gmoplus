<!-- PDF Export link -->

{if $listing_data.Status === 'active'}
    <li>
        <a target="_blank" href="{pageUrl key='PdfExport' vars='listingID='|cat:$listing_data.ID}">
            {$lang.title_pdf_export}

            <img style="vertical-align: top; margin-top: 1px;"
                src="{$smarty.const.RL_PLUGINS_URL}PdfExport/static/icon.png"
                alt="{$lang.title_pdf_export}"
                title="{$lang.title_pdf_export}"/>
        </a>
    </li>
{/if}

<!-- PDF Export link end -->
