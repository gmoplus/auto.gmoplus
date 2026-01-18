{addCSS file=$smarty.const.RL_PLUGINS_URL|cat:'shoppingCart/static/cash.css'}

<div class="d-flex justify-content-center pt-3 pb-3 cash-or"><span class="ml-3 mr-3">or</span></div>
<div class="d-flex justify-content-center mb-5">
    <label><input type="radio" name="gateway" value="cash" />{$lang.shc_payment_cash}</label>
</div>
