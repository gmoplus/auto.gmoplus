<!-- The Location Filter box in filters section -->

{* Reassign config to apply correct CSS styles to the Location filter (prevent problem with scrollbar) *}
{php}
    $cfHomePageSpecialBlockOrigin = $this->_tpl_vars['home_page_special_block'];
    $this->_tpl_vars['home_page_special_block'] = ['Key' => 'geo_filter_box'];
{/php}

{include file=$smarty.const.RL_PLUGINS|cat:'multiField/tplHeader.tpl'}
{include file=$smarty.const.RL_PLUGINS|cat:'multiField/geo_box.tpl'}

{php}
    $this->_tpl_vars['home_page_special_block'] = $cfHomePageSpecialBlockOrigin;
    unset($cfHomePageSpecialBlockOrigin);
{/php}

<!-- The Location Filter box in filters section end -->
