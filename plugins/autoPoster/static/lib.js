var AutoPosters = function() {
    var self = this;
    this.listing_id = 0;

    /**
     * Sending post to the provider handlers
     *
     * @param {int} listing_id - Sending listing ID
     */
    this.sendPost = function (listing_id) {
        self.setID(listing_id);
        var data = {
            item: 'getProviders'
        };

        self.sendAjax(data, function (providers) {
            self.providersSendPost(providers);
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
            function(response){
                callback(response);
            }, 'json')
    };

    /**
     * Send post to the wall via provider
     * @param {array} providers - Array of the active providers
     */
    this.providersSendPost = function(providers) {
        if (providers) {
            $.each(providers, function(index, provider) {
                var data = {
                    item: 'sendListingToProvider',
                    provider: provider,
                    listing_id: self.getID()
                };

                self.sendAjax(data, function(response){

                });
            });
        }
    };

    /**
     * Listing ID setter
     * @param {int} id
     */
    this.setID  = function (id) {
        self.listing_id = id;
    };

    /**
     * Listing ID getter
     * @returns {int}
     */
    this.getID = function () {
        return self.listing_id;
    };
};
