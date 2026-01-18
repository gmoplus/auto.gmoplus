<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: ROUTES.PHP
 *  
 *  The software is a commercial product delivered under single, non-exclusive,
 *  non-transferable license for one domain or IP address. Therefore distribution,
 *  sale or transfer of the file in whole or in part without permission of Flynax
 *  respective owners is considered to be illegal and breach of Flynax License End
 *  User Agreement.
 *  
 *  You are not allowed to remove this information from the file without permission
 *  of Flynax respective owners.
 *  
 *  Flynax Classifieds Software 2025 | All copyrights reserved.
 *  
 *  https://www.flynax.com
 ******************************************************************************/

$groupPrefix = '/' . str_replace(RL_DS, '/', RL_DIR) . 'plugins/accountSync';
$route->addGroup($groupPrefix . '/api/v1', function (\FastRoute\RouteCollector $route) {

    $route->get('/status', 'ApiController@checkStatus');
    $route->get('/disconnect', 'ApiController@disconnect');
    $route->post('/check-admin', 'ApiController@checkAdmin');

    // account type related routes
    $route->get('/account/types', 'ApiController@getAccountTypes');
    $route->get('/account/types/{key}', 'ApiController@getAccountType');
    $route->post('/account/types', 'ApiController@createAccountType');

    // account fields related routes
    $route->get('/account/fields', 'ApiController@getAllAccountFields');
    $route->get('/account/fields/{fieldKey}', 'ApiController@getAccountFieldInfo');
    $route->get('/account/{accountType}/fields', 'ApiController@getAccountTypeFields');
    $route->post('/account/fields', 'ApiController@createAccountField');

    // account related routed
    $route->get('/accounts', 'UserController@getAccounts');
    $route->post('/accounts', 'UserController@registerNewUser');
    $route->post('/accounts/quick', 'UserController@registerQuickNewUser');
    $route->post('/accounts/create', 'UserController@createUsers');
    $route->get('/accounts/syncUsers', 'UserController@syncUsers');
    $route->delete('/accounts/{email}', 'UserController@removeUser');
    $route->post('/accounts/{email}', 'UserController@updateUser');
    $route->post('/accounts/{email}/password', 'UserController@changeUserPassword');
    $route->post('/accounts/{email}/avatar', 'UserController@uploadUserAvatar');

    //todo: check, can I use condition in routes like {status|pending and etc}
    $route->post('/accounts/{email}/{status}', 'UserController@changeUserStatus');

    $route->post('/cache/account-types', 'CacheController@updateAccountTypes');
});
