
/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.2
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : LIB.JS
 *
 *	This script is a commercial software and any kind of using it must be
 *	coordinate with Flynax Owners Team and be agree to Flynax License Agreement
 *
 *	This block may not be removed from this file or any other files with out
 *	permission of Flynax respective owners.
 *
 *	Copyrights Flynax Classifieds Software | 2024
 *	https://www.flynax.com
 *
 ******************************************************************************/
const CategoryFilterClass = function () {
    'use strict';

    /**
     * Reference to self object
     */
    const self = this;

    /**
     * Show/hide other sub-categories
     */
    this.moreFilters = function () {
        $('div.filter-area ul').each(function () {
            if ($(this).find('li.hide').length > 0) {
                $(this).next().after('<ul class="hide"></ul>');
                $(this).next().next().append($(this).find('li.hide').show());
            }
        });

        $('div.filter-area a.more').click(function () {
            var pos, subCategories, tmp, width, offset;

            $('div.other_filters_tmp').remove();

            pos = $(this).offset();
            subCategories = $(this).next().html();
            tmp = '<div class="other_filters_tmp side_block">';
            tmp += '<div class="block_bg hlight"><ul></ul></div></div>';

            $('body').append(tmp);
            $('div.other_filters_tmp div ul').html(subCategories);

            width = $(this).width() + 5;
            offset = rlLangDir === 'ltr' ? pos.left + width : pos.left - $('div.other_filters_tmp').width();

            $('div.other_filters_tmp').css({top: pos.top, left: offset, display: 'block'});
        });

        $(document).click(function (event) {
            if ($(event.target).closest('.other_filters_tmp').length <= 0
                && $(event.target).attr('class') !== 'dark_12 more'
            ) {
                $('div.other_filters_tmp').remove();
            }
        });
    };

    /**
     * Checkbox call method
     */
    this.checkbox = function (obj, empty) {
        obj.parent().find('ul li input').click(function () {
            self.checkboxAction(obj, empty);
        });

        this.checkboxAction(obj, empty);
    };

    /**
     * Checkbox action method
     */
    this.checkboxAction = function (obj, empty) {
        var values = [], href;
        obj.parent().find('ul li').each(function () {
            if ($(this).find('input').is(':checked')) {
                values.push($(this).find('input').val());
            }
        });

        if (values.length > 0) {
            href = obj.find('a:first').attr('accesskey');
            href = href.replace('[replace]', values.join(','));

            obj.find('a:first').attr('href', href);
            obj.find('span').hide();
            obj.removeClass('dark single');
            obj.find('a:first').show();

            if (empty) {
                obj.find('a:last').hide();
                obj.find('span').html(lang.cf_apply_filter);
            }
        } else {
            obj.find('a:first').hide();
            obj.addClass('dark single');
            obj.find('span').show();

            if (empty) {
                obj.find('a:last').show();
                obj.removeClass('single');
                obj.find('span').html(lang.cf_remove_filter);
            }
        }
    };

    /**
     * Handler for enabling/disabling apply button in search (text) fields
     * @param {object} obj    - Dom container with fields
     * @param {bool}   values - Detect selected values
     */
    this.textFields = function (obj, values) {
        var $applyButton, $inactiveButton, $fromField, $toField, $currencyField;

        $applyButton = obj.find('a:first');
        $inactiveButton = !values ? obj.find('span') : obj.find('a.cf-remove');
        $fromField = obj.closest('.cf-parent-container').find('input[name="from"]');
        $toField = obj.closest('.cf-parent-container').find('input[name="to"]');
        $currencyField = obj.closest('.cf-parent-container').find('select[name="currency"]');

        if (!values) {
            $applyButton.hide();
            $inactiveButton.show();
        }

        $applyButton.click(function () {
            var min, max, url, currency;

            min = $fromField.val() ? parseFloat($fromField.val()) : 'min';
            max = $toField.val() ? parseFloat($toField.val()) : 'max';

            if (min || max) {
                url = $applyButton.attr('accesskey').replace('[replace]', min + '-' + max);
                url += rlConfig.mod_rewrite ? '/' : '';
                currency = $currencyField.length ? $currencyField.find('option:selected').val() : '';

                if (currency && currency !== '0') {
                    if (rlConfig.mod_rewrite) {
                        url += 'currency:' + currency + '/';
                    } else {
                        url += '&cf-currency:' + currency;
                    }
                }

                window.location.href = url;
            }
        });

        obj.closest('.cf-parent-container').find('input').keyup(function () {
            let min, max, filterAllowed = false;

            min = $fromField.val() ? parseFloat($fromField.val()) : null;
            max = $toField.val() ? parseFloat($toField.val()) : null;

            if (min !== null && max !== null && min < max) {
                filterAllowed = true;
            } else if (min !== null && min >= 0 && max === null) {
                filterAllowed = true;
            } else if (max !== null && max > 0 && min === null) {
                filterAllowed = true;
            }

            $inactiveButton[filterAllowed ? 'hide' : 'show']();
            $applyButton[filterAllowed ? 'show' : 'hide']();
        });
    };

    /**
     * Slider constructor
     * @since  2.7.0
     * @param  {object} options - Properties for building of slider
     *                          - Required: [key, from, to, step, minExist, maxExist, countsData]
     */
    this.slider = function (options) {
        let $field, $filterBlock, $counter, $applyButton, $emptyBlock;

        options.key = options.key.replace('-', '_');
        if (!options.key || !options.countsData) {
            console.log("Filter error: slider doesn't have required data.");
        } else {
            $field = $('input[name=slider_' + options.key + ']');

            if (!$field.length) {
                console.log("Filter error: input for slider doesn't exist.");
            }
        }

        $filterBlock  = $('div#cf_link_' + options.key);
        $counter     = $filterBlock.find('span.counter');
        $applyButton = $filterBlock.find('a');
        $emptyBlock  = $filterBlock.find('span.empty');

        if (rlConfig.mod_rewrite) {
            options.pattern = new RegExp(options.key.replace(/_/g, '-') + '[\:]([^/]+)');
        } else {
            options.pattern = new RegExp('cf-' + options.key.replace(/_/g, '-') + '[\=]([^/]+)');
        }

        flUtil.loadStyle(rlConfig.plugins_url + 'categoryFilter/static/bootstrap-slider.min.css');
        flUtil.loadScript(rlConfig.plugins_url + 'categoryFilter/static/bootstrap-slider.min.js', function () {
            $field.slider({
                min    : options.from,
                max    : options.to,
                step   : options.step,
                range  : true,
                tooltip: 'show',
                value  : [options.sliderMin, options.sliderMax],
                handle : 'custom',
                formatter: function (values) {
                    if (Array.isArray(values)) {
                        let firstNumber = values[0], secondNumber = values[1], result;

                        if (options.priceCurrency && options.currencyPosition) {
                            // Add separator symbol in prices
                            firstNumber   = firstNumber.toString().replace(/\B(?=(\d{3})+(?!\d))/g, rlConfig.price_delimiter);
                            secondNumber = secondNumber.toString().replace(/\B(?=(\d{3})+(?!\d))/g, rlConfig.price_delimiter);

                            if (options.showCents) {
                                firstNumber += rlConfig.price_separator + '00';
                                secondNumber += rlConfig.price_separator + '00';
                            }

                            firstNumber = options.currencyPosition === 'before'
                                ? options.priceCurrency + ' ' + firstNumber
                                : firstNumber + ' ' + options.priceCurrency;

                            secondNumber = options.currencyPosition === 'before'
                                ? options.priceCurrency + ' ' + secondNumber
                                : secondNumber + ' ' + options.priceCurrency;
                        }


                        result = firstNumber + ' - ' + secondNumber;

                        return result;
                    }
                },
            });

            let svgClass         = 'grid-icon-fill';
            let specialTemplates = [
                'auto_flatty',
                'auto_main_red',
                'auto_rainbow_nova_wide',
                'boats_flatty',
                'escort_nova_wide',
                'escort_rainbow_nova_wide',
                'escort_velvet_flatty',
                'realty_rainbow_nova_wide',
            ];
            if ($.inArray(rlConfig.template_name, specialTemplates) >= 0) {
                svgClass = 'header-usernav-icon-fill';
            }

            $('.cf-slider .slider-handle.custom').html(
                `<svg xmlns=\"http://www.w3.org/2000/svg\" class=\"${svgClass}\" viewBox=\"0 0 16 16\">`
                    + "<circle cx=\"8\" cy=\"8\" r=\"8\"/>"
                    + "</svg>"
            );

            $field.on('slide', function (slideEvent) {
                let start, finish, total = 0, i, count, price, sign, countValue, replace;

                start = Number(slideEvent.value[0]);
                finish = Number(slideEvent.value[1]);

                // Count total count by selected range
                for (i = 0; i <= options.countsData.length; i++) {
                    if (options.countsData[i]) {
                        count = Number(options.countsData[i][0]);
                        price = Number(options.countsData[i][1]);

                        if (price >= start && price <= finish && count) {
                            total += count;
                        }
                    }
                }

                // Add real count
                countValue = '(' + total + ')';

                // Update total count in filter box
                if (total > 0) {
                    // Add "plus" to span with counter
                    if ((options.minExist !== -1 && options.maxExist !== -1)
                        && (start < options.minExist || finish > options.maxExist)
                    ) {
                        countValue = '(' + total + '+)';
                    }

                    $counter.html(countValue);

                    sign = rlConfig['mod_rewrite'] ? ':' : '=';
                    replace = !rlConfig['mod_rewrite'] ? 'cf-' : '';
                    replace += options.key.replace(/_/g, '-') + sign + start + '-' + finish;

                    $applyButton.attr('href', $applyButton.attr('href').replace(options.pattern, replace));

                    if (!$applyButton.is(':visible')) {
                        $emptyBlock.hide();
                        $applyButton.show();
                    }
                } else {
                    $counter.html(countValue);

                    if ($applyButton.is(':visible')) {
                        $applyButton.hide();
                        $emptyBlock.html(lang.cf_apply_filter).show();
                    }
                }
            });
        });
    };

    /**
     * Category tree handler (available for boxes on listing type page only)
     *
     * @since      2.10.0 - Restore the method to fix problem in templates without flynaxTpl.categoryTree() function (like services_rainbow)
     * @deprecated 2.5.0
     */
    this.categoryTree = function(){
        $('.filter-area .cat-tree-cont').each(function(){
            var count = $(this).find('ul.cat-tree > li').length;
            var desktop_limit_top = 10;
            var desktop_limit_bottom = 25;

            if (count <= 0) {
                return;
            }

            $(this).find('ul.cat-tree > li span.toggle').click(function(){
                $(this).closest('li').find('ul').toggle();

                var parent = $(this).closest('.cat-tree-cont');
                if (parent.hasClass('mCustomScrollbar')) {
                    parent.addClass('limit-height').mCustomScrollbar('update');
                }

                $(this).text(trim($(this).text()) == '+' ? '-' : '+');
            });

            if ($(this).find('ul.cat-tree > li span.toggle:contains("+")').length == 0) {
                $(this).find('ul.cat-tree > li span.toggle').hide();
            }

            var current_media_query = media_query;
            $(window).resize(function(){
                if (media_query != current_media_query && $(this).hasClass('mCustomScrollbar')) {
                    $(this).addClass('limit-height').mCustomScrollbar('update');
                    current_media_query = media_query;
                }
            });

            if (count > desktop_limit_top && count <= desktop_limit_bottom) {
                var gt = desktop_limit_top - 1;
                $(this).find('ul.cat-tree > li:gt(' + gt + ')').addClass('rest');

                $(this).find('div.cat-toggle').removeClass('hide').click(function(){
                    $(this).prev().find('> li.rest').toggle();
                });
                $(this).removeClass('limit-height');
            }
            else if (count > desktop_limit_bottom) {
                $(this).mCustomScrollbar();
            }
            else {
                $(this).removeClass('limit-height');
            }
        });
    };
};

const categoryFilter = new CategoryFilterClass();
