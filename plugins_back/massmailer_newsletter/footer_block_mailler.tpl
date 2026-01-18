<script class="fl-js-dynamic">
    {literal}
        $(document).ready(function(){
            $parent = $('.newsletter');

            /**
             * OLX clone template fix
             * @todo - Remove once the olx_clone template will use newsletterAction() function
             */
            if ($parent.parent().hasClass('main-wrapper')) {
                $parent.find('.newsletter_email').attr('id', 'newsletter_email');
            }

            $button = $parent.find('.subscribe_user');
            var $email  = $parent.find('.newsletter_email');
            newsletterAction($button, $email, '', true);
        });
    {/literal}
</script>
