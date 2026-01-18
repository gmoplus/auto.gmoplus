
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
 *	Copyrights Flynax Classifieds Software | 2023
 *	https://www.flynax.com
 *
 ******************************************************************************/

var stripe;

var stripeBtnEl = '#btn-checkout, #form-checkout input[type="submit"]';
var stripeBtnVal = $(stripeBtnEl).val();
stripeBtnEl.disabled = true;

fetch(rlConfig['ajax_url'] + '?mode=stripeKeys')
    .then(function(result) {
        return result.json();
    })
    .then(function(data) {
        return setupElements(data);
    })
    .then(function({stripe, card, clientSecret }) {
        stripeBtnEl.disabled = false;

        $(stripeBtnEl).click(function(event) {
            $(stripeBtnEl).val(lang['loading']);
            event.preventDefault();
            pay(stripe, card, clientSecret);
        });
    });

var setupElements = function(data) {
    if (data.stripeAccount) {
        stripe = Stripe(data.publishableKey, {stripeAccount: data.stripeAccount, locale: rlLang});
    } else {
        stripe = Stripe(data.publishableKey, {locale: rlLang});
    }
    var elements = stripe.elements();
    var style = {
        base: {
            color: "#32325d",
            fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
            fontSmoothing: "antialiased",
            fontSize: "16px",
            "::placeholder": {
                color: "#aab7c4"
            }
        },
        invalid: {
            color: "#fa755a",
            iconColor: "#fa755a"
        }
    };

    var card = elements.create("card", {style: style, hidePostalCode: rlConfig['stripe_index']});
    card.mount('#card-element');

    return {
        stripe: stripe,
        card: card,
        clientSecret: data.clientSecret
    };
};

var handleAction = function(clientSecret) {
    stripe.handleCardAction(clientSecret).then(function(data) {
        if (data.error) {
            printMessage('Your card was not authenticated, please try again');
            $(stripeBtnEl).val(stripeBtnVal);
        } else if (data.paymentIntent.status === "requires_confirmation") {
            fetch(rlConfig['ajax_url'] + '?mode=stripePaymentIntent', {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    paymentIntentId: data.paymentIntent.id
                })
            })
            .then(function(result) {
                return result.json();
            })
            .then(function(json) {
                if (json.error) {
                    printMessage('error', json.error);
                    $(stripeBtnEl).val(stripeBtnVal);
                } else {
                    orderComplete(clientSecret);
                }
            });
        }
    });
};

/*
 * Collect card details and pays for the order
 */
var pay = function(stripe, card) {
    stripe
    .createPaymentMethod('card', card)
    .then(function(result) {
        if (result.error) {
            printMessage('error', result.error.message);
            $(stripeBtnEl).val(stripeBtnVal);
        } else {
            var orderData = {
                paymentMethodId: result.paymentMethod.id
            };

            return fetch(rlConfig['ajax_url'] + '?mode=stripePaymentIntent', {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(orderData)
            });
        }
    })
    .then(function(result) {
        return result ? result.json() : {};
    })
    .then(function(paymentData) {
        if (paymentData.requiresAction) {
            // Request authentication
            handleAction(paymentData.clientSecret);
        } else if (paymentData.error) {
            printMessage('error', paymentData.error);
            $(stripeBtnEl).val(stripeBtnVal);
        } else if (paymentData.clientSecret) {
            orderComplete(paymentData.clientSecret);
        }
    });
};

var orderComplete = function(clientSecret) {
    stripe.retrievePaymentIntent(clientSecret).then(function(result) {
        var paymentIntent = result.paymentIntent;
        var paymentIntentJson = JSON.stringify(paymentIntent, null, 2);

        var data = {
            mode: 'stripeComplete',
            paymentIntent: paymentIntentJson
        };
        flUtil.ajax(data, function(response) {
            if (response.status == 'OK') {
                window.location.href = response.successUrl;
            }
            if (status == 'ERROR') {
                window.location.href = response.errorUrl;
            }
        });
    });
};
