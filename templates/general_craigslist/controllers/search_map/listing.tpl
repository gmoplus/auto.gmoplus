{assign var='count_replace' value=`$smarty.ldelim`count`$smarty.rdelim`}

<script class="fl-js-dynamic">
{literal}

mapSearch.gridModeSwitcher = function(){};
mapSearch.getGridMode = function(){
    return 'grid row';
}

{/literal}
</script>

<script id="tmplListing" type="text/x-jquery-tmpl">
{literal}

${($data.count_in_location = '{/literal}{$lang.count_properties_in_location|replace:$count_replace:'[count]'}{literal}'),''}
${($data.group_location_hint = '{/literal}{$lang.group_location_hint}{literal}'),''}
${($data.seo_base = '{/literal}{$smarty.const.SEO_BASE}{literal}'),''}

<article class="item col-sm-6{{if fd == 1 && gc == 1}} featured{{/if}}{{if gc > 1}} group{{/if}}" id="map_ad_${ID}">
    <div class="main-column relative clearfix">
        <a {{if gc == 1}}target="_blank"{{/if}} href="{{if gc > 1}}javascript://{{else}}${url}{{/if}}">
            <div class="picture{{if !img}} no-picture{{/if}}">
                <img src="{{if img}}${img}{{else}}{/literal}{$rlTplBase}{literal}img/blank_10x7.gif{{/if}}" 
                {{if img_x2}}srcset="${img_x2} 2x"{{/if}} />
                {{if tmplMapListingHookData}}{{html tmplMapListingHookData}}{{/if}}
                {{if gc > 1}}<mark class="group"><span>{{html String(count_in_location).replace(/(\[count\])/gi, gc)}}</span></mark>{{/if}}
                {{if gc == 1 && price}}
                    <div class="price-tag">
                        <span>${price}</span>
                        {{if srk == 2 && tf}}/ ${tf}{{/if}}
                    </div>
                {{/if}}
                {{if pct || gc > 1}}
                    <span class="count">{{if gc > 1}}${gc}{{else}}${pct}{{/if}}</span>
                {{/if}}
            </div>
        </a>
        <ul class="card-info">
            {{if gc > 1}}
                <li class="group-info">
                    ${group_location_hint}
                </li>
            {{else}}
                {{if dt}}
                <li class="before-title">
                    <span class="data">${dt}</span>
                    <div id="fav_${ID}" class="favorite add"><span class="icon"></span></div>
                </li>
                {{/if}}
                <li class="title">
                    <a target="_blank" href="${url}" title="${title}" class="link-large">${title}</a>
                </li>
                {{if bds > 0 || bts > 0 || sf}}
                <li class="services">
                    {{if bds > 0}}<span title="" class="badrooms">${bds}</span>{{/if}}{{if bts > 0}}<span title="" class="bathrooms">${bts}</span>{{/if}}{{if sf}}<span title="" class="square_feet">${sf}</span>{{/if}}
                </li>
                {{/if}}
                <li class="fields">
                    {{each fields_data}}
                        <span>${$value}</span>
                    {{/each}}
                </li>
            {{/if}}
        </ul>
    </div>
</article>

{/literal}
</script>
