
/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: LIB.JS
 *  
 *  The software is a commercial product delivered under single, non-exclusive,
 *  non-transferable license for one domain or IP address. Therefore distribution,
 *  sale or transfer of the file in whole or in part without permission of Flynax
 *  respective owners is considered to be illegal and breach of Flynax License End
 *  User Agreement.
 *  
 *  You are not allowed to remove this information from the file without permission
 *  of Flynax respective owners.
 *  
 *  Flynax Classifieds Software 2025 | All copyrights reserved.
 *  
 *  https://www.flynax.com
 ******************************************************************************/

/**
 * Seller Review/Rating plugin main js class
 * @constructor
 */
const SellerReviewsClass = function () {
    const self = this;

    /**
     * Value of selected star
     * @type {boolean}
     */
    this.commentStar = false;

    /**
     * Draw the containers with account rating, stars and comments count
     * @param accountInfo
     */
    this.drawAccountRating = function (accountInfo) {
        let rating = accountInfo.Rating, link = accountInfo.commentsCountLink, text = accountInfo.commentsCountText;

        if (!rating || !link || !text) {
            return;
        }

        let accountRating,
            stars,
            $sellerInfoSection = $('.seller-short .seller-info'),
            addClass = rlPageInfo.controller === 'listing_details' ? ' mb-2' : '';

        if (srrConfigs.isFlatty) {
            addClass += ' pt-3';
        }

        if ($sellerInfoSection.find('.account-rating').length > 0) {
            $sellerInfoSection.find('.account-rating').remove();
        }

        if (srrConfigs.ratingModule && rating > 0) {
            accountRating = $('<span>', {class: 'srr-account-rating'}).text(rating);

            stars = $('<div>', {class: 'srr-stars d-flex ml-2 mr-2'});
            for (let i = 1; i <= rating; i++) {
               stars.append(
                   $('<span>', {class: 'srr-star srr-star-page'})
               )
            }

            // Put next star with partially filled if rating have fractional part of a number
            if (rating % 1 !== 0) {
                stars.append(
                    $('<span>', {class: 'srr-star srr-star-page inactive'}).append(
                        $('<span>', {'style': 'width: ' + rating.toString().split('.')[1] + '0%;'})
                    )
                );
            }
        }

        $sellerInfoSection.find('li:last').after(
            $('<li>', {class: 'account-rating counter d-flex align-items-center flex-wrap' + addClass}).append(
                accountRating,
                stars,
                $('<div>', {class: 'srr-comments-count'}).append(
                    $('<a>', {href: link, class: 'srr-account-comments'}).html(text).click(function () {
                        if (isLogin || !srrConfigs.loginToAccess) {
                            $('#tab_srr_comments a').trigger('click');
                        } else {
                            printMessage('warning', lang.srr_login_to_see_comments);
                        }
                    })
                )
            )
        )
    }

    /**
     * Get actual account rating
     * @param id
     */
    this.loadAccountRating = function (id) {
        if (!id) {
            return;
        }

        flUtil.ajax({mode: 'srrLoadAccountRating', accountID: id}, function (response) {
            if (response && response.status && response.status === 'OK') {
                self.drawAccountRating(response);
            } else {
                printMessage('error', lang.system_error);
            }
        });
    }

    /**
     * Get comments from database and load to content
     *
     * @since 1.1.0 - Added "filters" parameter
     *
     * @param page
     * @param filters
     */
    this.loadComments = function (page, filters) {
        if (!srrConfigs.accountInfo) {
            return;
        }

        page = page ? page : 1;

        flUtil.ajax(
            {mode: 'srrGetComments', accountID: srrConfigs.accountInfo.Account_ID, page: page, filters: filters},
            function (response) {
                if (response && response.status === 'OK' && response.commentsHtml) {
                    if (filters) {
                        $('#area_srr_comments .comments-list').remove();
                        $('#area_srr_comments .srr-count-by-stars').after(response.commentsHtml);
                    } else {
                        $('#area_srr_comments #srr_comments').empty().append(response.commentsHtml);

                        self.reviewsFilterHandler();

                        srrConfigs.accountInfo.Comments_Count = Number(response.commentsCount);

                        window.commentFormRevertHandler = false;
                        if (srrConfigs.accountInfo.Comments_Count === 0) {
                            let $commentsSection = $('#srr_comments div');

                            if (isLogin || !srrConfigs.loginToPost) {
                                window.commentFormRevertHandler = true;
                                let $newCommentForm = $('#srr-add-new-comment-form');
                                $commentsSection.after($newCommentForm.removeClass('d-none'));
                                self.mouseEventHandler($newCommentForm);
                            } else {
                                let $loginForm = $('#login_modal_source > *').clone(true, true);
                                $loginForm.find('.caption_padding').hide();
                                $loginForm.find('form').before(
                                    $('<div>', {class: 'text-notice text-center'}).html(lang.srr_login_to_post)
                                );

                                /**
                                 * Adapt old login forms
                                 * @todo - Remove this when compatibility will be > 4.8.0
                                 */
                                if ($loginForm.find('.submit-cell .name').length) {
                                    $loginForm.find('.submit-cell .name').each(function () {
                                        $(this).hide();
                                        $(this).parent().find('input').addClass('w-100');

                                        if (!$(this).parent().hasClass('buttons')) {
                                            let columnTitle = $(this).html();
                                            $(this).parent().find('input').attr('placeholder', columnTitle);
                                        }
                                    });
                                }

                                $commentsSection.after(
                                    $('<div>', {id: 'srr-add-new-comment-form', class: 'mx-auto'}).append(
                                        $loginForm
                                    )
                                );
                            }
                        }
                    }

                    let $pagination = $('#area_srr_comments #srr_pagination');
                    $pagination.empty();
                    if (response.paginationHTML) {
                        let paginationFile = rlConfig.tpl_base + 'components/pagination/_pagination.js';

                        /**
                         * @todo - Remove custom pagination when compatibility will be >= 4.9.0
                         */
                        if (response.oldPagination) {
                            paginationFile = rlConfig.plugins_url + 'sellerReviews/static/_pagination.js';
                        }

                        flUtil.loadScript(paginationFile, function () {
                            $pagination.append(response.paginationHTML);
                            flPaginationHandler($pagination);
                        });
                    }
                } else {
                    printMessage('error', lang.system_error);
                }
            }
        )
    }

    /**
     * Load comments in popup
     */
    this.loadCommentsInPopup = function () {
        flUtil.loadStyle(rlConfig.tpl_base + 'components/popup/popup.css');
        flUtil.loadScript(rlConfig.tpl_base + 'components/popup/_popup.js', function() {
            $('body').popup({
                click  : false,
                content: $('<div>', {id: 'area_srr_comments', class: 'w-100'}).append(
                    $('<section>', {id: 'srr_comments'}).html(lang.loading),
                    $('<section>', {id: 'srr_pagination'}),
                ),
                caption: lang.srr_tab,
                width  : 1000,
                height : 700,
                onShow : self.loadComments(),
                onClose: function () {
                    if (window.commentFormRevertHandler) {
                        let $baseContainer = $('.main-wrapper').length ? $('.main-wrapper') : $('#wrapper');

                        $baseContainer.append(
                            $('#srr_comments #srr-add-new-comment-form').addClass('d-none')
                        )
                        window.commentFormRevertHandler = false;
                    }
                    this.destroy();
                }
            });
        });
    }

    /**
     * Show form for adding new comment
     */
    this.showAddCommentForm = function () {
        flUtil.loadStyle(rlConfig.tpl_base + 'components/popup/popup.css');
        flUtil.loadScript(rlConfig.tpl_base + 'components/popup/_popup.js', function() {
            let $content, onShow, onClose;

            if (isLogin || !srrConfigs.loginToPost) {
                $content = $('#srr-add-new-comment-form .fieldset .body form');
                onShow   = function ($popup) {
                    self.mouseEventHandler($popup);
                }
                onClose = function ($interface) {
                    $interface.find('.body > *').appendTo($('#srr-add-new-comment-form .fieldset .body > div'));
                    this.destroy();
                }
            } else {
                $content = $('#login_modal_source > *').clone();
                $content.find('.caption_padding').hide();
                $content.find('form').before(
                    $('<div>', {class: 'text-notice text-center'}).html(lang.srr_login_to_post)
                );

                /**
                 * Adapt old login forms
                 * @todo - Remove this when compatibility will be > 4.8.0
                 */
                if ($content.find('.submit-cell .name').length) {
                    $content.find('.submit-cell .name').each(function () {
                        $(this).hide();
                        $(this).parent().find('input').addClass('w-100');

                        if (!$(this).parent().hasClass('buttons')) {
                            let columnTitle = $(this).html();
                            $(this).parent().find('input').attr('placeholder', columnTitle);
                        }
                    });
                }

                onShow = function ($popup) {
                    // Prevent closing the popup by click on label with checkbox
                    if ($popup.find('.remember-me')) {
                        $popup.find('input[id^=css_INPUT]').attr('id', 'css_INPUT_99999');
                        $popup.find('label[for^="css_INPUT"]').attr('for', 'css_INPUT_99999');
                    }
                };
            }

            $('body').popup({
                click  : false,
                content: $content,
                caption: lang.srr_add_comment,
                width  : 360,
                onShow : onShow,
                onClose: onClose,
            });
        });
    }

    /**
     * Save comment to database
     * @param $form
     * @returns {boolean}
     */
    this.addNewComment = function ($form) {
        let $button = $form.find('input[type=submit]');

        $button.addClass('disabled').attr('disabled', true).val(lang.loading);

        flUtil.ajax({
            mode        : 'srrAddNewComment',
            accountID   : srrConfigs.accountInfo.Account_ID,
            authorID    : rlAccountInfo.ID,
            author      : $form.find('#srr_author').val(),
            title       : $form.find('#srr_title').val(),
            message     : $form.find('#srr_message').val(),
            securityCode: $form.find('#srr_security_code').val(),
            star        : self.commentStar
        }, function(response) {
            $button.removeClass('disabled').attr('disabled', false).val(lang.srr_add_comment);

            if (response) {
                if (response.status === 'ERROR') {
                    response.errorsFields.forEach(function (field) {
                        $(field).addClass('error');
                    })

                    let $errorsContainer = $form.find('.comments-popup-errors');

                    if (Number(srrConfigs.accountInfo.Comments_Count) === 0) {
                        printMessage('error', response.errors, response.errorsFields);
                    } else {
                        $errorsContainer.empty();
                        $errorsContainer.append(response.errors);
                        $errorsContainer.removeClass('d-none');
                    }

                    $form.find('input[type=text], textarea').keydown(function() {
                        $errorsContainer.empty().addClass('d-none');
                        $form.find('input,textarea').filter('.error').removeClass('error');
                    });
                } else {
                    $('form[name="srr_add_comment"]').closest('.popup').find('.close').trigger('click');
                    printMessage('notice', response.message);

                    $form.find('#srr_title,#srr_message').val('');
                    $form.find('.srr-star-add').removeClass('srr-star-add_active');
                    $form.find('#srr_security_img').click();

                    // Reset captcha/reCaptcha widget
                    if (typeof ReCaptcha === 'object' && typeof ReCaptcha.resetWidgetByIndex === 'function') {
                        ReCaptcha.resetWidgetByIndex($form.find('.gptwdg').attr('data-recaptcha-index'));
                    } else {
                        $form.find('#srr_security_code').val('');
                    }

                    if (window.commentFormRevertHandler) {
                        let $baseContainer = $('.main-wrapper').length ? $('.main-wrapper') : $('#wrapper');

                        $baseContainer.append(
                            $('#srr_comments #srr-add-new-comment-form').addClass('d-none')
                        )
                        window.commentFormRevertHandler = false;
                    }

                    self.loadComments();

                    if (srrConfigs.autoApproval) {
                        self.loadAccountRating(srrConfigs.accountInfo.Account_ID);
                    }
                }
            } else {
                printMessage('error', lang.system_error);
            }
        });

        return false;
    }

    /**
     * @param $popup
     */
    this.mouseEventHandler = function ($popup) {
        let $star = $popup.find('.srr-star-add'), timer;
        self.commentStar = false;

        $star.mouseover(function() {
            let id = $(this).attr('id').split('_')[2];

            if (self.commentStar) {
                timer = setTimeout(function () {self.commentFill(id, $popup);}, 700);
            } else {
                self.commentFill(id, $popup);
            }
        }).click(function() {
            self.commentStar = $(this).attr('id').split('_')[2];
        }).mouseout(function() {
            clearTimeout(timer);

            if (self.commentStar) {
                return false;
            }

            $star.removeClass('srr-star-add_active');
        });
    }

    /**
     * @param id
     * @param $popup
     */
    this.commentFill = function (id, $popup) {
        self.commentStar = false;
        id               = Number(id);

        $popup.find('.srr-star-add').removeClass('srr-star-add_active');

        for (let i = 1; i <= id; i++) {
            $popup.find('#srr_star_' + i).addClass('srr-star-add_active');
        }
    }

    /**
     * @since 1.1.0
     */
    this.reviewsFilterHandler = function () {
        let $starsRow = $('.srr-count-by-stars .stars-item-row');

        $starsRow.hover(
            function () {
                if (!$(this).parent().hasClass('fixed-hover')) {
                    $(this).addClass('hover');
                    $(this).parent().addClass('hover');
                }
            }, function () {
                if (!$(this).parent().hasClass('fixed-hover')) {
                    $(this).removeClass('hover');
                    $(this).parent().removeClass('hover');
                }
            }
        );

        $starsRow.click(function () {
            let $starsRow = $(this);
            let $starsContainer = $starsRow.parent();
            let rating = null;
            let $selectedRow = $starsContainer.find('.stars-item-row.fixed-hover');
            let $selectedRating = Number($selectedRow.data('rating'));

            if ($selectedRating && $selectedRating !== Number($starsRow.data('rating'))) {
                $selectedRow.removeClass('fixed-hover hover');
                $selectedRow.parent().removeClass('fixed-hover hover');
                self.loadComments(1, {Rating: null});
                return;
            }

            if (!$starsRow.hasClass('fixed-hover')) {
                $starsRow.addClass('fixed-hover');
                $starsContainer.addClass('fixed-hover');
                rating = Number($starsRow.data('rating'));
            } else {
                $starsRow.removeClass('fixed-hover');
                $starsContainer.removeClass('fixed-hover');
            }

            self.loadComments(1, {Rating: rating});
        });
    }
};

const sellerReviews = new SellerReviewsClass();
