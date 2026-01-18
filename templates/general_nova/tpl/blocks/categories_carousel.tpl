<!-- categories carousel -->

{if $category_menu}
    {assign var='category_parent_ids' value=','|explode:$category.Parent_IDs}

    <section class="categories__block">
        <div class="categories__container point1">
            <div class="owl-carousel d-flex owl-hidden">
                {foreach from=$category_menu item='item'}
                <div class="item categories__item{if $category.ID == $item.ID || $item.ID|in_array:$category_parent_ids} categories__item_active{/if} col-4 col-sm-3 col-md-2 col-lg-1">
                    <a href="{$item.href}"
                       class="categories__link text-center"
                       id="{$item.Key}">
                       {if $item.icon && is_readable($item.icon)}
                       {fetch file=$item.icon}
                       {/if}
                       {$item.Name}
                   </a>
                </div>
                {/foreach}
            </div>
        </div>
    </section>

    <script class="fl-js-dynamic">
    {literal}

    $(function(){
        flUtil.loadScript(rlConfig['tpl_base']  + 'js/owl.carousel.min.js', function(){
            $('.owl-carousel').owlCarousel({
                loop: true,
                rtl: rlLangDir == 'rtl' ? true : false,
                margin: 0,
                nav: true,
                dots: false,
                navText: [],
                navElement: 'div',
                responsive: {
                    0: {
                        items: 3
                    },
                    576: {
                        items: 4
                    },
                    768: {
                        items: 6
                    },
                    992: {
                        items: 8
                    },
                    1200: {
                        items: 12,
                        nav: false
                    }
                }
            });

            $('.owl-carousel .item').removeClass(function (index, className) {
                return (className.match (/(^|\s)col-\S+/g) || []).join(' ');
            });

            $('.owl-hidden').removeClass('owl-hidden');
        });
    });
    {/literal}
    </script>
{/if}

<!-- categories carousel -->
