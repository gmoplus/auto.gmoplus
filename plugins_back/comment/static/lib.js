
/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: RLCOMMENT.CLASS.PHP
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
 *  Flynax Classifieds Software 2024 | All copyrights reserved.
 *  
 *  https://www.flynax.com
 ******************************************************************************/

var commentClass = function(){
    this.comment_star = 0;
    this.comment_timer = '';
    this.listingId = null;
    this.messageLength = null;
    this.typeView = null;
    this.autoApproval = false;
    this.allowView = false;

    var self = this;

    /**
     * Initializing method
     *
     * @since 4.1.0 - allowView parameter added
     *
     * @param int listingId     - Listing ID
     * @param int messageLength - Message text length
     * @param string typeView   - Display comments view, tab or box
     * @param bool autoApproval - Comment auto approval option
     * @param bool allowView    - Allow user to see the comment
     */
    this.init = function(listingId, messageLength, typeView, autoApproval, allowView){
        this.listingId = listingId;
        this.messageLength = messageLength;
        this.typeView = typeView;
        this.autoApproval = autoApproval;
        this.allowView = allowView;

        this.formSubmitHandler();
        this.textareaCountHandler();
        this.commentPaging();
        this.mouseEventHandler();
    }

    this.commentPaging = function() {
        $('body').on('click', 'ul#comment_paging li:not(.transit)', function() {
            if ($(this).hasClass('active')) {
                return;
            }

            if ($(this).find('a').attr('accesskey')) {
                var page = parseInt($(this).find('a').attr('accesskey'));
            } else {
                var page = parseInt($(this).find('a').html());
            }
            self.getComments(page);
        });
    }

    this.formSubmitHandler = function() {
        var $form = $('form[name=add_comment]');
        var $submitButton = $form.find('input[type=submit]');

        $form.submit(function() {
            $submitButton
                .addClass('disabled')
                .attr('disabled', true)
                .val(lang['loading']);

            flUtil.ajax({
                mode: 'addComment',
                listing_id: self.listingId,
                comment_author: $('#comment_author').val(),
                comment_title: $('#comment_title').val(),
                comment_message: $('#comment_message').val(),
                comment_security_code: $('#comment_security_code').val(),
                comment_star: self.comment_star
            }, function(response) {
                if (response) {
                    $submitButton
                        .removeClass('disabled')
                        .attr('disabled', false)
                        .val(lang['comment_add_comment']);

                    if (self.autoApproval && response.informer) {
                        $('.comments-link-to-source').replaceWith(response.informer);
                    }

                    if (response.status === 'ERROR') {
                        if (self.typeView === 'tab') {
                            printMessage('error', response.data, response.error_fields);
                        } else {
                            var $errorCont = $('.comments-popup-errors');
                            $errorCont.empty();
                            $errorCont.append(response.data);
                            $errorCont.removeClass('d-none');

                            $form.find('input[type=text], textarea').change(function() {
                                $errorCont.empty().addClass('d-none');
                            });
                        }
                    } else {
                        if (self.typeView === 'box') {
                            $('.popup .close').trigger('click');
                        }

                        printMessage('notice', response.mess);

                        $form.find('#comment_title,#comment_message').val('');
                        $('.comment-star-add').removeClass('comment-star-add_active');
                        $('img#comment_security_img').click();

                        if (self.allowView ) {
                            $('#comments_dom').empty();
                            $('#comments_dom').append(response.data);

                            self.commentPaging();
                        }

                        if (self.autoApproval) {
                            var $counter = $('.statistics .counters a[href="#comments"] .count');
                            $counter.text(parseInt($counter.text()) + 1);
                        }

                        // Reset captcha/reCaptcha widget
                        if (typeof ReCaptcha === 'object' && typeof ReCaptcha.resetWidgetByIndex === 'function') {
                            ReCaptcha.resetWidgetByIndex($form.find('.gptwdg').attr('data-recaptcha-index'));
                        } else {
                            $form.find('#comment_security_code').val('');
                        }
                    }
                } else {
                    printMessage('error', lang.system_error);
                }
            });

            return false;
        });
    };
    
    this.textareaCountHandler = function() {
        $('#comment_message').textareaCount({
            'maxCharacterSize': self.messageLength,
            'warningNumber': 20
        });
    };

    this.mouseEventHandler = function () {
        var $star = $('.comment-star-add');

        $star.mouseover(function() {
            var id = $(this).attr('id').split('_')[2];

            if (self.comment_star) {
                self.comment_timer = setTimeout(self.comment_fill(id), 700);
            } else {
                self.comment_fill(id);
            }
        }).click(function() {
            self.comment_star = $(this).attr('id').split('_')[2];
        }).mouseout(function() {
            clearTimeout(self.comment_timer);

            if (self.comment_star) {
                return false;
            }

            $star.removeClass('comment-star-add_active');
        });
    }

    this.comment_fill = function(id) {
        self.comment_star = 0;
        id = parseInt(id);

        $('.comment-star-add').removeClass('comment-star-add_active');

        for (var i = 1; i <= id; i++) {
            $('#comment_star_' + i).addClass('comment-star-add_active');
        }
    }

    this.getComments = function(page) {
        flUtil.ajax({
            mode: 'getComments',
            listing_id: self.listingId,
            page: page
        },
        function(response) {
            if (response) {
                if (response.status === 'OK') {
                    $('#comments_dom').empty();
                    $('#comments_dom').append(response.data);
                    $("#comment_loading_bar").fadeOut("fast");
                }
            } else {
                printMessage('error', lang.system_error);
            }
        })
    }
}  
