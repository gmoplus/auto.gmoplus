{if $isStripeConfigured}
    <!-- stripe plugin -->
    <style>
    {literal}
    div.stripe-submit-cell {
        padding-bottom: 13px;
        *zoom: 1;
    }
    div.stripe-submit-cell:after {
        clear: both;
        content: '';
        display: table;
    }

    div.stripe-submit-cell .stripe-name {
        float: left;
        width: 120px;
        padding: 0 10px 0 0;
        min-height: 32px;
    }
    div.stripe-submit-cell > div.field {
        overflow: hidden;
        min-height: 32px;
        float: left;
        margin-right: 15px;
    }
    div.stripe-submit-cell > div.field .stripe-sub-name {
        display: block;
    }
    div.stripe-submit-cell.buttons {
        padding: 10px 0 0 0;
    }
    /* Variables */
    .sr-combo-inputs-row {
      box-shadow: 0px 0px 0px 0.5px rgba(50, 50, 93, 0.1),
        0px 2px 5px 0px rgba(50, 50, 93, 0.1), 0px 1px 1.5px 0px rgba(0, 0, 0, 0.07);
      border-radius: 7px;
    }

    /* Stripe Element placeholder */
    .sr-card-element {
      margin: 20px 0;
      max-width: 450px;
      border: 1px solid #e6e6e6;
      padding: 12px 8px;
      border-radius: 4px;
      transition: border-color 0.3s ease;
    }
    .sr-card-element:hover {
      border-color: #999999;
    }
    /* Responsiveness */
    @media (max-width: 720px) {
      .sr-header__logo {
        background-position: center;
      }
      .sr-payment-summary {
        text-align: center;
      }
      .sr-content {
        display: none;
      }
    }

    /* todo: spinner/processing state, errors, animations */

    .spinner,
    .spinner:before,
    .spinner:after {
      border-radius: 50%;
    }
    .spinner {
      color: #ffffff;
      font-size: 22px;
      text-indent: -99999px;
      margin: 0px auto;
      position: relative;
      width: 20px;
      height: 20px;
      box-shadow: inset 0 0 0 2px;
      -webkit-transform: translateZ(0);
      -ms-transform: translateZ(0);
      transform: translateZ(0);
    }
    .spinner:before,
    .spinner:after {
      position: absolute;
      content: "";
    }
    .spinner:before {
      width: 10.4px;
      height: 20.4px;
      background: var(--accent-color);
      border-radius: 20.4px 0 0 20.4px;
      top: -0.2px;
      left: -0.2px;
      -webkit-transform-origin: 10.4px 10.2px;
      transform-origin: 10.4px 10.2px;
      -webkit-animation: loading 2s infinite ease 1.5s;
      animation: loading 2s infinite ease 1.5s;
    }
    .spinner:after {
      width: 10.4px;
      height: 10.2px;
      background: var(--accent-color);
      border-radius: 0 10.2px 10.2px 0;
      top: -0.1px;
      left: 10.2px;
      -webkit-transform-origin: 0px 10.2px;
      transform-origin: 0px 10.2px;
      -webkit-animation: loading 2s infinite ease;
      animation: loading 2s infinite ease;
    }

    @-webkit-keyframes loading {
      0% {
        -webkit-transform: rotate(0deg);
        transform: rotate(0deg);
      }
      100% {
        -webkit-transform: rotate(360deg);
        transform: rotate(360deg);
      }
    }
    @keyframes loading {
      0% {
        -webkit-transform: rotate(0deg);
        transform: rotate(0deg);
      }
      100% {
        -webkit-transform: rotate(360deg);
        transform: rotate(360deg);
      }
    }

    /* Animated form */
    .hidden {
      display: none;
    }

    @keyframes field-in {
      0% {
        opacity: 0;
        transform: translateY(8px) scale(0.95);
      }
      100% {
        opacity: 1;
        transform: translateY(0px) scale(1);
      }
    }

    @keyframes form-in {
      0% {
        opacity: 0;
        transform: scale(0.98);
      }
      100% {
        opacity: 1;
        transform: scale(1);
      }
    }
    {/literal}
    </style>
    <!-- stripe plugin -->
    <script src="{$smarty.const.RL_PLUGINS_URL}stripe/static/{$libFile}.js" defer></script>
    {if $libFile == 'lib.subscription'}
        <div class="submit-cell">
            <div class="name">{$lang.card_holder_name}</div>
            <div class="field">
                <input type="text" name="card_name" id="card_name" class="wauto" maxlength="35" size="35" value="{$smarty.post.card_name}" />
            </div>
        </div>
    {/if}
    <div class="sr-input sr-card-element" id="card-element"></div>
    <script>
        {literal}
        $(document).on('click', 'ul#payment_gateways>li', function() {
            if ($(this).find('input').val() != 'stripe') {
                $('#custom-form').html('');
                $(stripeBtnEl).unbind('click');
            }
        });
        {/literal}
    </script>
    <!-- end stripe plugin -->
{else}
    <script>
    {literal}
    $(document).ready(function() {
        printMessage('error', '{/literal}{$lang.stripe_seller_payment_details_empty}{literal}');
    });
    {/literal}
    </script>
{/if}
