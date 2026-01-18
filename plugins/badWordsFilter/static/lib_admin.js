var BadWordFilterClass = function () {
    /**
     * @type {BadWordFilterClass}
     */
    var self = this;

    /**
     * Language ID
     * @type {number}
     */
    this.lang_id = 0;

    /**
     * Set lang ID
     *
     * @param {number} id - Language ID
     */
    this.setLangId = function (id) {
        id = Math.abs(id);
        id = id == parseInt(id, 10) ? parseInt(id, 10) : 0;

        self.lang_id = id;
    };

    /**
     * Getting language ID
     *
     * @returns {number|*}
     */
    this.getLangId = function () {
        return self.lang_id;
    };

    /**
     * Class init method
     */
    this.init = function () {
        /* adding bad word */
        $('#add_badword').click(function () {
            $(this).val(lang['loading']);

            var $bad_word_value_field = $('#badword_value');
            var bad_word = $bad_word_value_field.val();

            if (!bad_word) {
                printMessage('error', bwLang['bw_fill_bad_word']);
                $bad_word_value_field.addClass('error');
                $(this).val(bwLang['add']);
                return false;
            }

            $bad_word_value_field.removeClass('error');
            return self.addBadWord(bad_word);
        });

    };

    /**
     * Sending AJAX request to add bad word
     *
     * @param {string} bad_word - New bad word
     */
    this.addBadWord = function (bad_word) {
        var lang_code = self.getLangId() ? self.getLangId() : 0;

        var data = {
            item: 'addBadWord',
            bad_word: bad_word,
            lang_id: lang_code
        };

        self.sendAjax(data, function (response) {
            if (response.status == 'OK') {
                printMessage('notice', response.message);
                badwordsGrid.reload();
                $('#badword_value').val('');
            } else {
                printMessage('error', response.message);
            }

            $('#add_badword').val(bwLang['add']);
        });
    };

    /**
     * Sending AJAX request to remove bad word from database
     *
     * @param {number} id  - Removing bad word ID
     */
    this.removeBadWord = function (id) {
        var data = {
            item: 'removeBadWord',
            id: id
        };

        self.sendAjax(data, function (response) {
            if (response || response.message) {
                if (response.status == 'OK') {
                    printMessage('notice', bwLang['bw_removed_successfully']);
                    badwordsGrid.reload();
                } else {
                    printMessage('error', response.message);
                }
            }
        });
    };

    /**
     * Sending AJAX
     *
     * @param {object} data     - Sending data
     * @param {object} callback - Callback function
     */
    this.sendAjax = function (data, callback) {
        $.post(rlConfig["ajax_url"], data,
            function (response) {
                callback(response);
            }, 'json')
    };
};

var badWordFilter = new BadWordFilterClass();
