<!-- smart search tpl -->

<div class="smart-search position-relative w-100">
    <form autocomplete="off" class="smart-search__form d-flex w-100" method="post" action="{pageUrl key='search'}#keyword_tab">
        <input type="hidden" name="action" value="search" />
        <input type="hidden" name="form" value="keyword_search" />

        <div class="w-100 position-relative">
            <input name="f[keyword_search]" autocomplete="off" class="smart-search__input w-100" type="text" placeholder="{$lang.keyword_search_hint}" />
            <div class="smart-search__close align-items-center justify-content-center">
                <svg viewBox="0 0 12 12" class="header-usernav-icon-fill">
                    <use xlink:href="#close-icon"></use>
                </svg>
            </div>
        </div>
        <input class="smart-search__submit" type="submit" value="{$lang.search}" />
    </form>

    <div class="smart-search__results-wrapper">
        <div class="smart-search__results-cont w-100">
            <div class="text-center pt-3 pb-3 date">{$lang.loading}</div>
        </div>
    </div>
</div>

<script id="smart-search-item" type="text/x-jsrender">
    <[%if ~type == 'category'%]a[%else%]div[%/if%] class="smart-search__results-item smart-search__results-item_[%:~type%] align-items-center d-flex cursor-pointer"
        [%if ~type == 'category'%]href="[%:url%]"[%/if%]
        >
        <svg viewBox="0 0 [%if ~type == 'category'%]23 20[%else%]22 22[%/if%]" class="smart-search__results-item-icon flex-glow smart-search__results-item-icon_[%:~type%] flex-shrink-0">
            <use xlink:href="#[%if ~type == 'category'%]folder-icon[%else%]magnifying-glass-icon[%/if%]"></use>
        </svg>
        <div class="smart-search__results-item-text d-flex flex-fill shrink-fix">
            [%if names%]
                [%for names%]
                    <span>[%:~highlight(name, ~query)%]</span>
                [%/for%]
            [%else%]
                <span>[%:~highlight(name, ~query)%]</span>
            [%/if%]
        </div>
        <svg viewBox="0 0 7 12" class="category-menu__category-arrow ml-2 flex-shrink-0">
           <use xlink:href="#arrow-right-icon"></use>
        </svg>
    </[%if ~type == 'category'%]a[%else%]div[%/if%]>
</script>

<script class="fl-js-dynamic">
{literal}

$(function(){
    let timer = false;
    let $form = $('.smart-search__form');
    let $input = $('.smart-search__input');
    let $main = $('.smart-search');
    let $cont = $('.smart-search__results-cont');
    let $closeButton = $('.smart-search__close');
    let position = 0;
    let query = '';

    let setResultsTest = function(text){
        let html = '<div class="text-center pt-3 pb-3 date">' + text + '</div>';
        $cont.empty().append(html);
    }

    let closeResults = function(){
        $main.removeClass('smart-search_opened');
        setResultsTest(lang['loading']);
        resetActive();
    }

    let highlightMatches = function(name, query) {
        if (!name || !query) {
            return name;
        }

        let min_lengh = 3;
        let query_parts = query.split(' ');

        for (let i in query_parts) {
            if (!query_parts[i] || query_parts[i].length < min_lengh) {
                continue;
            }

            let rgx = new RegExp('(' + query_parts[i] + ')', 'gi')
            name = name.replace(rgx, '<b>$1</b>');
            min_lengh--;
        }

        return name;
    }

    let drawActive = function(){
        $cont.find('.smart-search__results-item_active').removeClass('smart-search__results-item_active');

        if (!position) {
            return
        }

        let index = position - 1;
        $cont.find('.smart-search__results-item:eq('+ index +')').addClass('smart-search__results-item_active');
    }

    let resetActive = function(){
        position = 0;
        drawActive();
    }

    let navigation = function(keyCode){
        let end = $input.val().length;
        $input.get(0).setSelectionRange(end, end);

        let count = $cont.find('.smart-search__results-item').length;

        if (!count) {
            position = 0;
            return;
        }

        if (keyCode == 40 && position < count) {
            position++;
        } else if (keyCode == 38 && position > 1) {
            position--;
        }

        drawActive();
    }

    $form.on('submit', function(){
        if (position > 0) {
            return false
        }
    });

    $input.on('keyup', function(e){
        if (e.keyCode == 13 && position > 0) {
            let index = position - 1;
            position = 0; // Reset position to allow form submit
            $cont.find('.smart-search__results-item:eq('+ index +')').trigger('click');
        }

        if ([38, 40].indexOf(e.keyCode) >= 0) {
            navigation(e.keyCode);
            e.stopPropagation();
            e.preventDefault();
            return false;
        }

        if (query == $(this).val()) {
            return;
        }

        resetActive();

        clearTimeout(timer);

        query = $(this).val();

        if (query.length <= 3) {
            closeResults();
            return;
        }

        $main.addClass('smart-search_opened');

        timer = setTimeout(function(){
            let data = {
                ajaxKey: 'smartSearch',
                mode: 'smartSearch',
                lang: rlLang,
                query: query
            };
            flUtil.ajax(data, function(response, status){
                if (status == 'success' && response.status == 'OK') {
                    if (response.keywords.length || response.categories.length) {
                        $cont.empty().append(
                            $('#smart-search-item').render(response.keywords, {
                                type: 'keyword',
                                highlight: highlightMatches,
                                query: query
                            }),
                            $('#smart-search-item').render(response.categories, {
                                type: 'category',
                                highlight: highlightMatches,
                                query: query
                            })
                        );
                    } else {
                        setResultsTest(lang['nothing_found_for_char'].replace('{char}', query));
                    }
                }
            });
        }, 700);
    }).on('focus', function(){
        $main.addClass('smart-search_focus');

        if ($cont.find('.smart-search__results-item').length) {
            $main.addClass('smart-search_opened');
        }
    }).on('blur', function(){
        $main.removeClass('smart-search_focus');
    });

    $cont.on('click', '.smart-search__results-item', function(){
        if ($(this).hasClass('smart-search__results-item_keyword')) {
            let text = $(this).find('.smart-search__results-item-text > span:last').text();

            if (!text) {
                return;
            }

            $input.val(text);
            $form.submit();
        } else {
            $(this).get(0).click();
        }
    }).mouseenter(function(){
        resetActive();
    });

    $closeButton.click(function(){
        $input.val('');
        closeResults();
    });

    $(document).bind('click touchstart', function(event){
        if (!$(event.target).parents().hasClass('smart-search')) {
            $main.removeClass('smart-search_opened');
            resetActive();
        }
    });

    $(document).on('keyup', function(e){
        if (e.key == 'Escape') {
            $main.removeClass('smart-search_opened');
        }
    });
});

{/literal}
</script>

<!-- smart search tpl end -->
