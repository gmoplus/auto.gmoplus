<!-- SellerReviews header tpl -->

<style>
{literal}
:root {
    --ssr-active-star: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23ff8970'%3E%3Cpath d='M7.952.656a.226.226 0 00-.21.13l-2.22 4.738-5.112.688a.23.23 0 00-.125.393l3.737 3.617-.937 5.163a.234.234 0 00.09.227c.07.052.163.058.24.016l4.531-2.503 4.532 2.503c.077.042.17.036.24-.016a.233.233 0 00.09-.227l-.938-5.163 3.738-3.617a.228.228 0 00-.126-.393l-5.11-.688L8.148.786a.222.222 0 00-.197-.13z'/%3E%3C/svg%3E");
}
.srr-star {
    background-image: var(--ssr-active-star);
}
.srr-star.inactive {
    position: relative;
    background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23d9d9d9'%3E%3Cpath d='M7.952.656a.226.226 0 00-.21.13l-2.22 4.738-5.112.688a.23.23 0 00-.125.393l3.737 3.617-.937 5.163a.234.234 0 00.09.227c.07.052.163.058.24.016l4.531-2.503 4.532 2.503c.077.042.17.036.24-.016a.233.233 0 00.09-.227l-.938-5.163 3.738-3.617a.228.228 0 00-.126-.393l-5.11-.688L8.148.786a.222.222 0 00-.197-.13z'/%3E%3C/svg%3E");
}
.srr-star.inactive > span {
    background-image: var(--ssr-active-star);
    background-size: auto 100%;
    position: absolute;
    top: 0;
    left: 0;
    bottom: 0;
}
.srr-count-by-stars__total {
    height: 5px;
    background-color: #d9d9d9;
    border-radius: 3px;
    overflow: hidden;
}
.srr-count-by-stars__count {
    font-size: 0.875em;
    color: initial;
}
.srr-count-by-stars__active {
    background-color: grey;
}
.srr-count-by-stars.hover .stars-item-row:not(.hover),
.srr-count-by-stars.fixed-hover .stars-item-row:not(.fixed-hover) {
    opacity: 0.5;
}
.srr-count-by-stars.hover .stars-item-row:not(.hover) {
    transition: opacity 0.25s ease;
    transition-delay: 50ms;
}

/**
 * @todo - Remove next style once the plugin compatibility > 4.8.2
 */
{/literal}{if $tpl_settings.name == 'general_cragslist_wide' && $config.rl_version|version_compare:'4.9.0' < 0}{literal}
#srr_pagination ul.pagination > li {
    display: inline-block;
    vertical-align: top;
    height: 2.75rem;
    line-height: unset;
}
#srr_pagination ul.pagination > li.transit {
    padding: 0 20px;
}
#srr_pagination ul.pagination > li > a {
    font-size: 28px;
    width: unset;
    height: unset;
}
#srr_comments .comments-list .table-cell .value {
    word-wrap: break-word;
    overflow: hidden;
}
{/literal}{/if}{literal}

/* Seller Reviews/Rating plugin css styles */
#srr-add-new-comment-form {
    max-width: 355px;
}
.srr-star-page {
    width: 16px;
    height: 16px;
}
#srr_security_code ~ span {
    display: none;
}
.srr-star-add {
    width: 30px;
    height: 30px;
    opacity: .5;
    cursor: pointer;
}
.srr-account-info .srr-star-add {
    cursor: initial;
}
.srr-star-add_active {
    opacity: 1;
}

{**
 * @todo - Remove it when "compatible" will be >= 4.8.2
 *}
{/literal}{if $config.rl_version|version_compare:'4.8.2' < 0}{literal}
.w-50 {
    width: 50% !important;
}
.mx-auto {
    margin-right: auto !important;
    margin-left: auto !important;
}
.text-center {
    text-align: center !important;
}
{/literal}{/if}{literal}

{**
 * @todo - Remove it when "compatible" will be > 4.8.1
 *}
{/literal}{if $config.rl_version|version_compare:'4.8.1' <= 0}{literal}
.d-flex {
  display: -ms-flexbox !important;
  display: flex !important;
}
.d-none {
  display: none !important;
}
.font-weight-bold {
  font-weight: 700 !important;
}
.mt-1 {
  margin-top: 0.25rem !important;
}
.mb-1 {
  margin-bottom: 0.25rem !important;
}
.mb-2 {
  margin-bottom: 0.5rem !important;
}
.mb-3 {
  margin-bottom: 1rem !important;
}
.ml-2 {
  margin-left: 0.5rem !important;
}
.mr-2 {
  margin-right: 0.5rem !important;
}
.ml-3 {
  margin-left: 1rem !important;
}
.mr-3 {
  margin-right: 1rem !important;
}
.mt-3 {
  margin-top: 1rem !important;
}
.mt-5 {
  margin-top: 3rem !important;
}
.flex-wrap {
  -ms-flex-wrap: wrap !important;
  flex-wrap: wrap !important;
}
.align-items-center {
  -ms-flex-align: center !important;
  align-items: center !important;
}
.flex-fill {
  -ms-flex: 1 1 auto !important;
  flex: 1 1 auto !important;
}
.w-100 {
  width: 100% !important;
}
{/literal}{/if}{literal}

{/literal}
</style>

<script class="fl-js-dynamic">
    /**
     * @todo Remove it when compatibility will be >= 4.8.1
     */
    {literal}
    if (typeof lang.srr_tab === 'undefined') {
        lang.srr_tab                   = '{/literal}{$lang.srr_tab}{literal}';
        lang.srr_login_to_post         = '{/literal}{$lang.srr_login_to_post}{literal}';
        lang.srr_login_to_see_comments = '{/literal}{$lang.srr_login_to_see_comments}{literal}';
        lang.srr_add_comment           = '{/literal}{$lang.srr_add_comment}{literal}';
        lang.srr_login_to_see_comments = '{/literal}{$lang.srr_login_to_see_comments}{literal}';
    }
    {/literal}

    let srrConfigs = [];
    srrConfigs.displayMode         = '{$config.srr_display_mode}';
    srrConfigs.ratingModule        = {if $config.srr_rating_module}true{else}false{/if};
    srrConfigs.loginToPost         = {if $config.srr_login_post}true{else}false{/if};
    srrConfigs.maxSymbolsInMessage = Number({if $config.srr_message_symbols_number}{$config.srr_message_symbols_number}{else}0{/if});
    srrConfigs.autoApproval        = {if $config.srr_auto_approval}true{else}false{/if};
    srrConfigs.loginToAccess       = {if $config.srr_login_access}true{else}false{/if};
    srrConfigs.accountInfo         = JSON.parse('{if $srrAccountInfo}{$srrAccountInfo|@json_encode}{/if}');
    srrConfigs.isFlatty            = {if $tpl_settings.name|strpos:'_flatty' !== false}true{else}false{/if};
</script>

<!-- SellerReviews header tpl end -->
