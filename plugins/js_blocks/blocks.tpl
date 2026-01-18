{assign var='boxID' value=$smarty.get.custom_id}

var linkHead  = document.createElement('link');
linkHead.href = '{$smarty.const.RL_PLUGINS_URL}js_blocks/static/remote_adverts.css';
linkHead.type = 'text/css';
linkHead.rel  = 'stylesheet';

var include_style = true;
var scripts = document.getElementsByTagName('link');

{literal}
for (var i = 0; i < scripts.length; i++) {
    if (scripts[i].href && scripts[i].href == linkHead.href) {
        include_style = false;
        break;
    }
}

if (include_style) {
    document.getElementsByTagName('head').item(0).appendChild(linkHead);
}
{/literal}

{literal}if (typeof raData == 'undefined') {
    var raData = {};
}{/literal}

var boxID           = '{$boxID}';
raData.{$boxID}     = {literal}{{/literal}out: '', maxPage: 0, clickedPages: []{literal}}{/literal};
raData.{$boxID}.grid_mode = {if $smarty.get.box_view == 'grid'}true{else}false{/if};
raData.{$boxID}.out += '<div id="jListingPaging_{$boxID}" class="jListingPaging"></div>';

{literal}
/* Add CSS styles for box
   @todo - Remove old scheme of applying styles (without object) in future major update */
if (typeof raDataStyles !== 'undefined' && typeof raDataStyles[boxID] !== 'undefined') {
    raData[boxID].styles = raDataStyles[boxID];
}
{/literal}

{if !empty($smarty.get.per_page)}
    {assign var="per_page" value=$smarty.get.per_page}
{else}
    {assign var="per_page" value=4}
{/if}

{if $smarty.get.per_row && $smarty.get.per_row != '4' && $smarty.get.box_view == 'grid'}
    {math equation='round(100/per_row, 3)' assign='flex_width' per_row=$smarty.get.per_row}
{/if}

{if $listings}

raData.{$boxID}.out += '<ul id="{$boxID}_page_1" class="jListingPage{if $smarty.get.box_view == 'grid'} jListingPage_grid-view{/if}">';

{foreach from=$listings item='listing' name="fList"}
    {if !$listing.url}
        {assign var="url" value=$smarty.const.RL_URL_HOME|cat:$listing.Page_path|cat:'/'|cat:$listing.Path|cat:'/'}
        {assign var="url2" value='-l'|cat:$listing.ID|cat:'.html'}
    {/if}

    raData.{$boxID}.out += '<li{if $flex_width} style="max-width: {$flex_width}%;flex: 0 0 {$flex_width}%;"{/if}><div id="jlisting_{$listing.ID}" class="jListingItem{if empty($listing.Main_photo)} jListingItem_no-picture{/if}" onmouseover="changeCss(this, \'' + boxID + '\')" onmouseout="restoreCss(this, \'' + boxID + '\')" {if $smarty.get.direction == 'horizontal'}style="display:inline-block;margin-right:5px;*display:inline;*zoom:1"{/if} onclick="location.href=\'{$listing.url}{if $config.ra_statistics}?r={$tmp_code}{/if}\'">';
    raData.{$boxID}.out += '<div class="jListingPicture">';
    raData.{$boxID}.out += '<a href="{$listing.url}{if $config.ra_statistics}?r={$tmp_code}{/if}">';
    raData.{$boxID}.out += '<img style="width:180px; height:120px;" alt="{$listing.listing_title|escape:quotes}" title="{$listing.listing_title|escape:quotes}" class="jListingImg" src="{if !empty($listing.Main_photo)}{$smarty.const.RL_FILES_URL}{$listing.Main_photo}{else}{$smarty.const.RL_URL_HOME}templates/{$config.template}/img/no-picture.png{/if}" {if $config.thumbnails_x2}srcset="{if $listing.Main_photo_x2}{$smarty.const.RL_FILES_URL}{$listing.Main_photo_x2}{else}{$smarty.const.RL_URL_HOME}templates/{$config.template}/img/@2x/no-picture.png{/if} 2x"{/if}></a></div>';
    raData.{$boxID}.out += '<div class="jListingData">';

    {if $listing.listing_title}
        raData.{$boxID}.out += '<div class="jListingTitle"><a class="jListingTitleLink" href="{$listing.url}{if $config.ra_statistics}?r={$tmp_code}{/if}">{$listing.listing_title|escape:quotes|regex_replace:"/[\r\t\n]/":"<br />"}</a></div>';
    {/if}

    {if $listing.fields[$config.price_tag_field].value}
        raData.{$boxID}.out += '<div class="jListingPrice">';
        raData.{$boxID}.out += '{$listing.fields[$config.price_tag_field].value}';
        {if $listing.sale_rent == 2 && $listing.fields.time_frame.value}
            raData.{$boxID}.out += '<span>/ {$listing.fields.time_frame.value}</span>';
        {/if}
        raData.{$boxID}.out += '</div>';
    {/if}

    {if $listing.fields}
    raData.{$boxID}.out += '<div class="jListingFields{if $smarty.get.field_names} jListingFields_with-names{/if}">';

    {foreach from=$listing.fields item='item' key='field' name='fListings'}
        {if !empty($item.value)
            && $item.Details_page
            && $item.Key != $config.price_tag_field
            && (($tpl_settings.listing_grid_except_fields && !$item.Key|in_array:$tpl_settings.listing_grid_except_fields)
                || !$tpl_settings.listing_grid_except_fields
            )
        }
            {if $smarty.get.field_names}
                raData.{$boxID}.out += '<div>';
            {/if}

            {if !empty($item.name) && $smarty.get.field_names}
                raData.{$boxID}.out += '<span class="jListingField_name">{$item.name|escape:quotes}: </span>';
            {/if}

            raData.{$boxID}.out += '<span class="jListingField_value">';
            {if $item.Type === 'textarea'}
                raData.{$boxID}.out += '{$item.value|strip_tags|truncate:50|escape:quotes|regex_replace:"/[\r\t\n]/":" "}';
            {else}
                raData.{$boxID}.out += '{$item.value|escape:quotes}';
            {/if}
            raData.{$boxID}.out += '</span>';

            {if $smarty.get.field_names}
                raData.{$boxID}.out +='</div>';
            {/if}
        {/if}
    {/foreach}

    raData.{$boxID}.out += '</div>';
    {/if}

    raData.{$boxID}.out += '</div>';
    raData.{$boxID}.out += '</div></li>';

    {if $smarty.foreach.fList.iteration%$per_page == 0}
        {assign var="page" value=$smarty.foreach.fList.iteration/$per_page+1}

        {if !$smarty.foreach.fList.last}
            raData.{$boxID}.out += '</ul>';
            raData.{$boxID}.out += '<ul id="{$boxID}_page_{$page|ceil}" class="jListingPage jListingHide" >';
            raData.{$boxID}.maxPage = {$page|ceil};
        {/if}
    {/if}
{/foreach}
raData.{$boxID}.out += '</ul>';
{if $smarty.get.direction == 'horizontal'}raData.{$boxID}.out += '<div style="clear:both;"></div>'{/if}
document.onready = build(boxID);
{else}
document.getElementById('{$boxID}').innerHTML = "{$lang.no_listings_found_deny_posting}";
{/if}
{literal}

function build(boxID) {
    document.getElementById(boxID).innerHTML = raData[boxID].out;

    var paging = '';
    for (var i = 1; i <= raData[boxID].maxPage; i++) {
        var page = document.getElementById(boxID + '_page_' + i);
        var add_class = i == 1 ? ' jListingPageItem-active' : '';
        paging += '<span class="jListingPageItem' + add_class + '" onmouseover="changeCss(this, \'' + boxID + '\')" onmouseout="restoreCss(this, \'' + boxID + '\')" id="' + boxID + '_pg_' + i + '" onclick="pageClick('+ i +', \'' + boxID + '\')">' + i + '</span>';
    }
    document.getElementById('jListingPaging_' + boxID).innerHTML = paging;

    var imgWidth, imgHeight, borderRadius, advertBg, advertBorderColor, advertBorder, imageBg, fieldFirstColor, fieldColor, pagingBg, pagingBorder;

    imgWidth = raData[boxID].styles && typeof raData[boxID].styles.conf_img_width !== 'undefined'
    ? raData[boxID].styles.conf_img_width
    : (typeof conf_img_width !== 'undefined' ? conf_img_width : false);

    imgHeight = raData[boxID].styles && typeof raData[boxID].styles.conf_img_height !== 'undefined'
    ? raData[boxID].styles.conf_img_height
    : (typeof conf_img_height !== 'undefined' ? conf_img_height : false);

    borderRadius = raData[boxID].styles && typeof raData[boxID].styles.conf_border_radius !== 'undefined'
    ? raData[boxID].styles.conf_border_radius
    : (typeof conf_border_radius !== 'undefined' ? conf_border_radius : false);

    advertBg = raData[boxID].styles && typeof raData[boxID].styles.conf_advert_bg !== 'undefined'
    ? raData[boxID].styles.conf_advert_bg
    : (typeof conf_advert_bg !== 'undefined' ? conf_advert_bg : false);

    advertBorderColor = raData[boxID].styles && typeof raData[boxID].styles.conf_advert_border_color !== 'undefined'
    ? raData[boxID].styles.conf_advert_border_color
    : (typeof conf_advert_border_color !== 'undefined' ? conf_advert_border_color : false);

    imageBg = raData[boxID].styles && typeof raData[boxID].styles.conf_image_bg !== 'undefined'
    ? raData[boxID].styles.conf_image_bg
    : (typeof conf_image_bg !== 'undefined' ? conf_image_bg : false);

    advertBorder = raData[boxID].styles && typeof raData[boxID].styles.conf_advert_border !== 'undefined'
    ? raData[boxID].styles.conf_advert_border
    : (typeof conf_advert_border !== 'undefined' ? conf_advert_border : false);

    fieldFirstColor = raData[boxID].styles && typeof raData[boxID].styles.conf_field_first_color !== 'undefined'
    ? raData[boxID].styles.conf_field_first_color
    : (typeof conf_field_first_color !== 'undefined' ? conf_field_first_color : false);

    fieldColor = raData[boxID].styles && typeof raData[boxID].styles.conf_field_color !== 'undefined'
    ? raData[boxID].styles.conf_field_color
    : (typeof conf_field_color !== 'undefined' ? conf_field_color : false);

    priceFieldColor = raData[boxID].styles && typeof raData[boxID].styles.conf_price_field_color !== 'undefined'
    ? raData[boxID].styles.conf_price_field_color
    : (typeof conf_price_field_color !== 'undefined' ? conf_price_field_color : false);

    pagingBg = raData[boxID].styles && typeof raData[boxID].styles.conf_paging_bg !== 'undefined'
    ? raData[boxID].styles.conf_paging_bg
    : (typeof conf_paging_bg !== 'undefined' ? conf_paging_bg : false);

    pagingBorder = raData[boxID].styles && typeof raData[boxID].styles.conf_paging_border !== 'undefined'
    ? raData[boxID].styles.conf_paging_border
    : (typeof conf_paging_border !== 'undefined' ? conf_paging_border : false);

    if (imgWidth) {
        setStyleByClass('img', 'jListingImg', 'width', imgWidth, boxID);
    }
    if (imgHeight) {
        setStyleByClass('img', 'jListingImg', 'height', imgHeight, boxID);
    }
    if (borderRadius) {
        setStyleByClass('div', 'jListingItem', 'borderRadius', borderRadius, boxID);
        setStyleByClass('span', 'jListingPageItem', 'borderRadius', borderRadius, boxID);
        setStyleByClass('span', 'jListingPageItem-active', 'borderRadius', borderRadius, boxID);

        if (borderRadius === '0px') {
            setStyleByClass('span', 'jListingPageItem', 'borderRadius', borderRadius, boxID);
        }
    }
    if (advertBg) {
        setStyleByClass('div', 'jListingItem', 'background', advertBg, boxID);
        setStyleByClass('span', 'jListingPageItem', 'background', advertBg, boxID);
        setStyleByClass('span', 'jListingPageItem-active', 'background', advertBg, boxID);
    }
    if (advertBorderColor) {
        setStyleByClass('div', 'jListingItem', 'borderColor', advertBorderColor, boxID);
        setStyleByClass('span', 'jListingPageItem', 'borderColor', advertBorderColor, boxID);
        setStyleByClass('span', 'jListingPageItem-active', 'borderColor', advertBorderColor, boxID);
    }
    if (imageBg) {
        setStyleByClass('img', 'jListingImg', 'backgroundColor', imageBg, boxID);
    }
    if (advertBorder) {
        setStyleByClass('div', 'jListingItem', 'border', advertBorder, boxID);
    }
    if (fieldFirstColor) {
        setStyleByClass('a', 'jListingTitleLink', 'color', fieldFirstColor, boxID);
    }
    if (fieldColor) {
        setStyleByClass('span', 'jListingField_name', 'color', fieldColor, boxID);
        setStyleByClass('span', 'jListingField_value', 'color', fieldColor, boxID);
    }
    if (priceFieldColor) {
        setStyleByClass('div', 'jListingPrice', 'color', priceFieldColor, boxID);
    }
    if (pagingBg) {
        setStyleByClass('span', 'jListingPageItem', 'background', pagingBg, boxID);
    }
    if (pagingBorder) {
        setStyleByClass('span', 'jListingPageItem', 'border', pagingBorder, boxID);
    }
}

function changeCss(obj, boxID) {
    var advertBgHover, advertBorderHover, pagingBgHover, pagingBorderHover;

    advertBgHover = raData[boxID].styles && typeof raData[boxID].styles.conf_advert_bg_hover !== 'undefined'
    ? raData[boxID].styles.conf_advert_bg_hover
    : (typeof conf_advert_bg_hover !== 'undefined' ? conf_advert_bg_hover : false);

    advertBorderHover = raData[boxID].styles && typeof raData[boxID].styles.conf_advert_border_hover !== 'undefined'
    ? raData[boxID].styles.conf_advert_border_hover
    : (typeof conf_advert_border_hover !== 'undefined' ? conf_advert_border_hover : false);

    pagingBgHover = raData[boxID].styles && typeof raData[boxID].styles.conf_paging_bg_hover !== 'undefined'
    ? raData[boxID].styles.conf_paging_bg_hover
    : (typeof conf_paging_bg_hover !== 'undefined' ? conf_paging_bg_hover : false);

    pagingBorderHover = raData[boxID].styles && typeof raData[boxID].styles.conf_paging_border_hover !== 'undefined'
    ? raData[boxID].styles.conf_paging_border_hover
    : (typeof conf_paging_border_hover !== 'undefined' ? conf_paging_border_hover : false);

    if (obj.className == 'jListingItem') {
        if (advertBgHover) {
            obj.style.background = advertBgHover;
        }
        if (advertBorderHover) {
            obj.style.border = advertBorderHover;
        }
    } else if (obj.className == 'jListingPageItem') {
        if (pagingBgHover) {
            obj.style.background = pagingBgHover;
        }
        if (pagingBorderHover) {
            obj.style.border = pagingBorderHover;
        }
    }
}

function restoreCss(obj, boxID) {
    var advertBg, advertBorder, pagingBg, pagingBorder;

    advertBg = raData[boxID].styles && typeof raData[boxID].styles.conf_advert_bg !== 'undefined'
    ? raData[boxID].styles.conf_advert_bg
    : (typeof conf_advert_bg !== 'undefined' ? conf_advert_bg : false);

    advertBorder = raData[boxID].styles && typeof raData[boxID].styles.conf_advert_border !== 'undefined'
    ? raData[boxID].styles.conf_advert_border
    : (typeof conf_advert_border !== 'undefined' ? conf_advert_border : false);

    pagingBg = raData[boxID].styles && typeof raData[boxID].styles.conf_paging_bg !== 'undefined'
    ? raData[boxID].styles.conf_paging_bg
    : (typeof conf_paging_bg !== 'undefined' ? conf_paging_bg : false);

    pagingBorder = raData[boxID].styles && typeof raData[boxID].styles.conf_paging_border !== 'undefined'
    ? raData[boxID].styles.conf_paging_border
    : (typeof conf_paging_border !== 'undefined' ? conf_paging_border : false);

    if (obj.className == 'jListingItem') {
        if (advertBg) {
            obj.style.background = advertBg;
        }
        if (advertBorder) {
            obj.style.border = advertBorder;
        }
    } else if (obj.className == 'jListingPageItem') {
        if (pagingBg) {
            obj.style.background = pagingBg;
        }
        if (pagingBorder) {
            obj.style.border = pagingBorder;
        }
    }
}

function pageClick(n, boxID) {
    {/literal}{if $config.ra_statistics}{literal}
    /* Send data for statistics */
    if (n != 1 && !(raData[boxID].clickedPages[n])) {
        raData[boxID].clickedPages.push(n);
        raData[boxID].clickedPages[n] = 1;
        var listings = document.getElementById(boxID + '_page_' + n).getElementsByClassName('jListingItem');
        var ids      = '';

        for (var i = 0; i < listings.length; i++) {
            ids += listings[i]['id'].split('_')[1] + ',';
        }

        var ping  = document.createElement('script');
        ping.src  = '{/literal}{$smarty.const.RL_PLUGINS_URL}js_blocks/blocks.inc.php?action=ping&ids={literal}' + ids;
        ping.type = 'text/javascript';
        document.getElementsByTagName('head').item(0).appendChild(ping);
    }
    {/literal}{/if}{literal}

    for (var k = 1; k <= raData[boxID].maxPage; k++) {
        if (k == n) {
            var className = 'jListingPage';
            className += raData[boxID].grid_mode ? ' jListingPage_grid-view' : '';

            document.getElementById(boxID + '_page_' + k).className = className;
            document.getElementById(boxID + '_pg_' + k).className   = 'jListingPageItem-active';

            if (typeof(conf_paging_bg_hover) != 'undefined' && typeof(conf_paging_border_hover) != 'undefined') {
                document.getElementById(boxID + '_pg_' + k).style.background = conf_paging_bg_hover;
                document.getElementById(boxID + '_pg_' + k).style.border = conf_paging_border_hover;
            }
        } else {
            document.getElementById(boxID + '_page_' + k).className = 'jListingHide';
            document.getElementById(boxID + '_pg_' + k).className = 'jListingPageItem';

            if (typeof(conf_paging_bg) != 'undefined' && typeof(conf_paging_border) != 'undefined') {
                document.getElementById(boxID + '_pg_' + k).style.background = conf_paging_bg;
                document.getElementById(boxID + '_pg_' + k).style.border = conf_paging_border;
            }
        }
    }
}

function setStyleByClass(t, c, p, v, parentID) {
    var elements = document.querySelectorAll(parentID ? ('#' + parentID + ' ' + t + '.' + c) : (t + '.' + c));

    for (var i = 0; i < elements.length; i++) {
        elements.item(i).style[p] = v;
    }
}
{/literal}
