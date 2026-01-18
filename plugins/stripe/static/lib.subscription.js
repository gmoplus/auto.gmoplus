
/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.2
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : LIB.SUBSCRIPTION.JS
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

fetch(rlConfig['ajax_url'] + '?mode=stripeKeys', {
        method: 'post',
        headers: {
            'Content-type': 'application/json',
        },
        body: JSON.stringify({
            subscription: true,
        })
    })
    .then(function(result) {
        return result.json();
    })
    .then(function(data) {
        return setupElements(data);
    })
    .then(function({card, customerId, priceId}) {
        stripeBtnEl.disabled = false;
        $(stripeBtnEl).click(function() {
            $(stripeBtnEl).val(lang['loading']);
            event.preventDefault();

            pay(card, customerId, priceId);
        });
    });

var setupElements = function(data) {
    stripe = Stripe(data.publishableKey, {locale: rlLang});
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

    card.on('change', function (event) {
        if (event.error) {
            printMessage('error', event.error.message);
            $(stripeBtnEl).val(stripeBtnVal);
        }
    });

    return {
        card: card,
        customerId: data.customerId,
        priceId: data.priceId
    };
};

function pay(card, customerId, priceId) {
    // Set up payment method for recurring usage
    let billingName = document.querySelector('#card_name').value;

    stripe
    .createPaymentMethod({
        type: 'card',
        card: card,
        billing_details: {
            name: billingName,
        },
    })
    .then((result) => {
        if (result.error) {
            printMessage('error', result.error.message);
            $(stripeBtnEl).val(stripeBtnVal);
        } else {
            createSubscription({
                customerId: customerId,
                paymentMethodId: result.paymentMethod.id,
                priceId: priceId,
            });
        }
    });
}

function createSubscription({ customerId, paymentMethodId, priceId }) {
    return (
        fetch(rlConfig['ajax_url'] + '?mode=stripeCreateSubscription', {
            method: 'post',
            headers: {
                'Content-type': 'application/json',
            },
            body: JSON.stringify({
                customerId: customerId,
                paymentMethodId: paymentMethodId,
                priceId: priceId,
            }),
        })
        .then((response) => {
            return response.json();
        })
        // If the card is declined, display an error to the user.
        .then((result) => {
            if (result.error) {
            // The card had an error when trying to attach it to a customer.
                throw result;
            }
            return result;
        })
        // Normalize the result to contain the object returned by Stripe.
        // Add the additional details we need.
        .then((result) => {
            return {
                paymentMethodId: paymentMethodId,
                priceId: priceId,
                subscription: result,
            };
        })
        // Some payment methods require a customer to be on session
        // to complete the payment process. Check the status of the
        // payment intent to handle these actions.
        .then(handlePaymentThatRequiresCustomerAction)
        // If attaching this card to a Customer object succeeds,
        // but attempts to charge the customer fail, you
        // get a requires_payment_method error.
        .then(handleRequiresPaymentMethod)
        // No more actions required. Provision your service for the user.
        .then(onSubscriptionComplete)
        .catch((error) => {
            // An error has happened. Display the failure to the user here.
            // We utilize the HTML element we created.
            printMessage('error', error.message);
            $(stripeBtnEl).val(stripeBtnVal);
        })
    );
}

function onSubscriptionComplete({
    subscription,
    priceId,
    paymentMethodId,
}) {
    // Payment was successful.
    if (subscription.status == 'active') {
        var data = {
            mode: 'stripeSubscriptionComplete',
            request: subscription
        };
        flUtil.ajax(data, function(response) {
            if (response.status == 'OK') {
                window.location.href = response.successUrl;
            }
            if (response.status == 'ERROR') {
                window.location.href = response.errorUrl;
            }
        });
    }
}

function handlePaymentThatRequiresCustomerAction({
    subscription,
    invoice,
    priceId,
    paymentMethodId,
    isRetry,
}) {
    if (subscription && subscription.status === 'active') {
        // Subscription is active, no customer actions required.
        return { subscription, priceId, paymentMethodId };
    }

    // If it's a first payment attempt, the payment intent is on the subscription latest invoice.
    // If it's a retry, the payment intent will be on the invoice itself.
    let paymentIntent = invoice ? invoice.payment_intent : subscription.latest_invoice.payment_intent;

    if (
        paymentIntent.status === 'requires_action' ||
        (isRetry === true && paymentIntent.status === 'requires_payment_method')
    ) {
        return stripe
            .confirmCardPayment(paymentIntent.client_secret, {
            payment_method: paymentMethodId,
        })
        .then((result) => {
            if (result.error) {
                // Start code flow to handle updating the payment details.
                // Display error message in your UI.
                // The card was declined (i.e. insufficient funds, card has expired, etc).
                throw result;
            } else {
                if (result.paymentIntent.status === 'succeeded') {
                    // Show a success message to your customer.
                    return {
                        priceId: priceId,
                        subscription: subscription,
                        invoice: invoice,
                        paymentMethodId: paymentMethodId,
                    };
                }
            }
        })
        .catch((error) => {
            printMessage(error.error.message);
            $(stripeBtnEl).val(stripeBtnVal);
        });
    } else {
        // No customer action needed.
        return { subscription, priceId, paymentMethodId };
    }
}

function handleRequiresPaymentMethod({
    subscription,
    paymentMethodId,
    priceId,
}) {
    if (subscription.status === 'active') {
        // subscription is active, no customer actions required.
        return { subscription, priceId, paymentMethodId };
    } else if (subscription.latest_invoice.payment_intent.status ==='requires_payment_method') {
        // Using localStorage to manage the state of the retry here,
        // feel free to replace with what you prefer.
        // Store the latest invoice ID and status.
        localStorage.setItem('latestInvoiceId', subscription.latest_invoice.id);
        localStorage.setItem(
        'latestInvoicePaymentIntentStatus',
        subscription.latest_invoice.payment_intent.status
        );
        throw { error: { message: 'Your card was declined.' } };
    } else {
        return { subscription, priceId, paymentMethodId };
    }
}
