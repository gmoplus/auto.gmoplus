<?php

use Flynax\Plugins\AccountSync\Adapters\AccountTypesAdapter;
use Flynax\Plugins\AccountSync\Models\MetaData;

if ($_GET['q'] == 'ext') {
    /* system config */
    require_once '../../../includes/config.inc.php';
    require_once RL_ADMIN_CONTROL . 'ext_header.inc.php';
    require_once RL_LIBS . 'system.lib.php';

    $reefless->loadClass('AccountSync', null, 'accountSync');
    $token = new \Flynax\Plugins\AccountSync\Models\Token();
    $meta = new MetaData();

    $limit = intval($_GET['limit']);
    $start = intval($_GET['start']);
    $data = array();

    if ($_GET['action'] == 'update') {
        $allSynchronizedDomains = $token->getAll();
        $accountTypeKey = $_REQUEST['id'];
        $api = new \Flynax\Plugins\AccountSync\API();
        $isExternal = false;

        $updatingDomainIndex = (int) str_replace('domain_', '', $rlValid->xSql($_REQUEST['field']));
        $updatingDomain = $allSynchronizedDomains[$updatingDomainIndex]['Domain'];

        $parentDomainInfo = $token->getInfoByID((int) $rlValid->xSql($_REQUEST['value']));
        $parentDomain = $parentDomainInfo['Domain'];

        $accountTypeInfo = array_change_key_case(AccountTypesAdapter::getInfoByKey($accountTypeKey), CASE_LOWER);
        if (!$accountTypeInfo) {
            $response = $api
                ->auth()
                ->withDomain($parentDomain)
                ->get(sprintf('account/types/%s', $accountTypeKey));

            if ($response->status == 200 && $response->body['code_phrase'] == 'account_type_info_success') {
                $accountTypeInfo = array_change_key_case($response->body['account_type'], CASE_LOWER);
                $isExternal = true;
            }
        }

        if ($accountTypeInfo && (bool) $_REQUEST['value']) {
            $meta = new MetaData();

            if ($isExternal) {
                $accountTypeAdapter = new AccountTypesAdapter();
                $accountTypeAdapter->create($accountTypeInfo);

                $allAccountTypes = AccountTypesAdapter::fetchAllTypes();
                $meta->set(RL_URL_HOME, MetaData::META_ACCOUNT_TYPES, $allAccountTypes);
                return;
            }

            $response = $api->auth()->withDomain($updatingDomain)->post('account/types', $accountTypeInfo);
            if ($response->status == 200 && $response->body['code_phrase']) {
                $fromCache = $meta->get('account_types', $updatingDomain);
                $fromCache[$accountTypeInfo['key']] = array(
                    'ID' => $accountTypeInfo['id'],
                    'Key' => $accountTypeInfo['key'],
                    'name' => $accountTypeInfo['name'],
                );
                $meta->set($updatingDomain, 'account_types', $fromCache);
            }
        }
    }

    if ($_GET['action'] == 'build') {
        $bcAStep = $lang['as_manage_domains'];
        $meta = new MetaData();
        $allTokens = $token->getAll();
        $data = AccountTypesAdapter::getUniqueAccountTypes();

        foreach ($data as $key => $value) {
            $domainsInfo = array();

            foreach ($allTokens as $token) {
                $domain = $token['Domain'];

                $domainAccountTypes = array_keys($meta->get('account_types', $domain));
                $domainsInfo[] = array(
                    'name' => preg_replace('(^https?://)', '', $domain),
                    'url' => $domain,
                    'is_sync' => (int) in_array($value['Key'], $domainAccountTypes),
                );
            }
            $data[$key]['domains'] = $domainsInfo;
        }

    } elseif ($_GET['action'] == 'manage_users') {
        $data = array();
        $uniqueUsers = $meta->get(MetaData::META_UNIQUE_USERS, RL_URL_HOME);
        $allDomainsAccountTypes = AccountTypesAdapter::getUniqueAccountTypes();
        $allTokens = $token->getAll();

        $data = array();
        foreach ($allDomainsAccountTypes as $key => $type) {
            $data[$key] = array(
                'key' => $type['Key'],
                'name' => $type['name'],
            );

            foreach ($allTokens as $token) {
                $infoByDomain = $uniqueUsers[$token['Domain']];
                $data[$key]['info'][] = array(
                    'domain' => preg_replace('(^https?://)', '', $token['Domain']),
                    'url' => $token['Domain'],
                    'stat' => $infoByDomain['statistic'][$type['Key']],
                );
            }
        }
    } else {
        $data = $token->getAll(true);

        foreach ($data as $key => $value) {
            $data[$key]['Status'] = 'Active';
        }
    }

    $count = count($data);

    $output['total'] = $count;
    $output['data'] = $data;

    echo json_encode($output);
} else {
    $action = $_GET['action'];
    $reefless->loadClass('AccountSync', null, 'accountSync');
    $meta = new MetaData();
    $token = new \Flynax\Plugins\AccountSync\Models\Token();
    $api = new \Flynax\Plugins\AccountSync\API();

    if ($action == 'build') {
        $bcAStep = $lang['as_manage_a_type'];
    }

    if ($action == 'build_fields') {
        $accountTypeKey = (string) $_GET['account_type'];
        $bcAStep = array();

        $bcAStep += array(
            array(
                'name' => $lang['as_manage_a_type'],
                'Controller' => $_GET['controller'],
                'Vars' => 'action=build',
            ),
            array(
                'name' => $lang['as_manage_fields'],
                'Controller' => $_GET['controller'],
                'Vars' => 'action=build_fields&account_type=' . $accountTypeKey,
            ),
        );

        $accountFields = $domainsInfo = array();
        $allSyncTokens = $token->getAll();

        $allAccountFieldsOfType = array();

        foreach ($allSyncTokens as $syncToken) {
            $fields = $meta->get(MetaData::META_ACCOUNT_FIELDS, $syncToken['Domain']);

            foreach ($fields[$accountTypeKey] as $key => $value) {
                $allAccountFieldsOfType[] = array(
                    'key' => $value['Key'],
                    'name' => $value['name'],
                );
            }
        }

        foreach ($allSyncTokens as $key => $syncToken) {
            $domainAccountFields = $meta->get(MetaData::META_ACCOUNT_FIELDS, $syncToken['Domain']);
            $parsedUrl = parse_url($syncToken['Domain']);
            $domainsInfo[$key] = array(
                'domain' => $syncToken['Domain'],
                'url' => preg_replace('(^https?://)', '', $syncToken['Domain']),
                'host' => $parsedUrl['host'],
            );
            $selectedAccountTypeFields = $domainAccountFields[$accountTypeKey];
            foreach ($allAccountFieldsOfType as $field) {
                $is_exist = false;
                if ($domainAccountFields['all']) {
                    $is_exist = is_numeric(array_search($field['key'], array_column($domainAccountFields['all'], 'Key')));
                }

                $domainsInfo[$key]['fields'][$field['key']] = array(
                    'key' => $field['key'],
                    'name' => $field['name'],
                    'is_exist' => $is_exist,
                );
            }
        }

        $rlSmarty->assign('domains_info', $domainsInfo);
    }

    if ($action == 'manage_users') {
        $bcAStep = $lang['as_manage_users'];
    }
}
