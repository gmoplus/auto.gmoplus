<script>
    lang['as_synchronize_to_websites_completed'] = '{$lang.as_synchronize_to_websites_completed}';
    lang['as_synchronize_to_websites']           = '{$lang.as_synchronize_to_websites}';
    lang['as_synchronize_confirm']               = '{$lang.as_synchronize_confirm}';
    lang['as_synchronize_in_progress']           = '{$lang.as_synchronize_in_progress}';
    lang['as_total_unique']                      = '{$lang.as_total_unique}';
    lang['as_manage_users']                      = '{$lang.as_manage_users}';
    lang['cache_updated']                        = '{$lang.cache_updated}';
    lang['as_something_wrong']                   = '{$lang.as_something_wrong}';
    lang['required']                             = '{$lang.required_fields}';
    lang['as_account_synchronization']           = '{$lang.as_account_synchronization}';
    lang['as_disconnected_confirm']              = '{$lang.as_disconnected_confirm}';
    lang['domain']                               = '{$lang.domain}';
    lang['account_type']                         = '{$lang.account_type}';
    lang['as_manage_domains']                    = '{$lang.as_manage_domains}';
    lang['as_synchronize_with_domains']          = '{$lang.as_synchronize_with_domains}';
    lang['as_sync']                              = '{$lang.as_sync}';
    lang['as_fields_synchronized']               = '{$lang.as_fields_synchronized}';
    lang['as_do_you_want_sync_field']            = '{$lang.as_do_you_want_sync_field}';
    lang['as_duplicate_user_info']               = '{$lang.as_duplicate_user_info}';
    

    rlConfig['as_user_limit'] = '{$config.as_user_limit}';

    {literal}
    $(document).ready(function() {
        new AccountSyncClass().init();
    });
{/literal}</script>
