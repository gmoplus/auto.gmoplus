<!-- categories block using category box component tpl -->

<style>{literal}
    .filter-area .categories-bc {
        display: none !important;
    }
{/literal}</style>

{include file=$smarty.const.RL_PLUGINS|cat:'categoryFilter/fields/categories_header.tpl'}
{include file=$componentDir|cat:'category-box/_category-box.tpl' typePage=true}

<!-- categories block using category box component tpl end -->
