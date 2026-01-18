<!-- verify link on add listing step done -->
    {pageUrl assign='verify_link' key='verify_photos' vars='id='|cat:$manageListing->listingID}
    <div class="text-notice" style="margin-top: 15px;">
        {assign var='replace' value='<a href="'|cat:$verify_link|cat:'">$1</a>'}
        {$lang.ls_step_done_verify|regex_replace:'/\[(.*)\]/':$replace}
    </div>
<!-- verify link on add listing step done end -->
