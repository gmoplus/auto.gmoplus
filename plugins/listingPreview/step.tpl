<!-- listing preview tpl -->

{if $cur_step == 'preview'}
    <div class="area_preview step_area">
        {include file='controllers'|cat:$smarty.const.RL_DS|cat:'listing_details.tpl'}

        <div>
            <form method="post" action="{buildFormAction}">
                <input type="hidden" name="step" value="preview" />

                <span class="form-buttons form">
                    <a href="{buildPrevStepURL show_extended=$manageListing->singleStep}">{$lang.edit_listing}</a>
                    <input type="submit" value="{$lang.listingPreview_confirm}" />
                </span>
            </form>
        </div>
    </div>

    <script class="fl-js-dynamic">
    {literal}

    $(function(){
        flynaxTpl.contactOwnerSubmit = function(){}
        flynaxTpl.contactSeller = function(){}

        /**
         * This code appears above the '.contact-seller' selector initialization on listing details,
         * that is why we forced to set setTimeout
         *
         * @todo - Remove the setTimeout once the '.contact-seller' selector initialization script 
         * class is switched to dynamic for flatty templates.
         */
        setTimeout(function(){
            $('.contact-seller, .contact-owner').unbind('click');

            $('.seller-short, .contact-buttons-personal').find('a, input[type=button]').click(function(){
                return false;
            });

            $('body').off('submit', 'form[name=contact_owner]');
            $('body').on('submit', 'form[name=contact_owner]', function(){
                return false;
            });

            if (typeof flynaxTpl.contactOwnerSubmit == 'function') {
                flynaxTpl.contactOwnerSubmit = function(){
                    return false;
                }
            }

            $('input[name=resume]').closest('form').unbind('submit');
        }, 50);

        /**
         * Unset click for .contact-owner' selector the second time after longer timeout due to
         * related script loads using loadScript method.
         */
        setTimeout(function(){
            $('.contact-owner').unbind('click');
        }, 1000);
    });

    {/literal}
    </script>
{/if}

<!-- listing preview tpl end -->
