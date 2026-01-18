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

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

$api_v1_attributes = ['prefix' => '/' . RL_DIR . 'api/v1/', 'namespace' => '\Flynax\Api\Http\Controllers\V1'];

Route::group($api_v1_attributes, function (Router $router) use ($api_autoload) {

    // Route::resource('pages', 'PageController');

    // App controller
    $router->get('/app/init', 'AppController@init');
    $router->post('/app/deleteFile', 'AppController@deleteFile');
    $router->get('/app/getMultiFieldNextFields', 'AppController@getMultiFieldNextFields');
    $router->get('/app/getSvgIcon', 'AppController@getSvgIcon');
    $router->post('/app/getCategories', 'AppController@getCategories');
    $router->post('/app/getCatTree', 'AppController@getCatTree');
    $router->get('/app/placesAutocomplete', 'AppController@placesAutocomplete');
    $router->get('/app/placesCoordinates', 'AppController@placesCoordinates');
    $router->get('/app/getHomeData', 'AppController@getHomeData');

    // Account controller
    $router->get('/app/getAccountDetails', 'AccountController@getAccountDetails');
    $router->get('/app/login', 'AccountController@login');
    $router->post('/app/registration', 'AccountController@registration');
    $router->post('/app/removeAccount', 'AccountController@removeAccount');
    $router->post('/app/uploadProfilePhoto', 'AccountController@uploadProfilePhoto');
    $router->get('/app/getAccountTypeFields', 'AccountController@getAccountTypeFields');
    $router->post('/app/changePassword', 'AccountController@changePassword');
    $router->post('/app/updateProfileEmail', 'AccountController@updateProfileEmail');
    $router->post('/app/editProfile', 'AccountController@editProfile');
    $router->post('/app/editAccount', 'AccountController@editAccount');
    $router->get('/app/getAccountSearchForm', 'AccountController@getAccountSearchForm');
    $router->post('/app/getDealersByChar', 'AccountController@getDealersByChar');
    $router->post('/app/searchDealers', 'AccountController@searchDealers');
    $router->get('/app/getAccountForms', 'AccountController@getAccountForms');
    $router->post('/app/resetPassword', 'AccountController@resetPassword');
    $router->post('/app/hybridAuthLogin', 'AccountController@hybridAuthLogin');
    $router->post('/app/hybridAuthLoginVerifyPassword', 'AccountController@hybridAuthLoginVerifyPassword');
    $router->post('/app/membershipPlansByType', 'AccountController@membershipPlansByType');
    $router->post('/app/upgradeAccountPlan', 'AccountController@upgradeAccountPlan');
    $router->post('/app/isSendMessage', 'AccountController@isSendMessage');
    $router->post('/app/savePushToken', 'AccountController@savePushToken');
    $router->post('/app/savePhoneClick', 'AccountController@savePhoneClick');
 
    // Payment controller
    $router->post('/app/validatedPayment', 'PaymentController@validatedPayment');
    $router->post('/app/confirmPayment', 'PaymentController@confirmPayment');
    $router->post('/app/paymentHistory', 'PaymentController@paymentHistory');
    $router->get('/app/callPaypal', 'PaypalController@callPaypal');

    // AddEditListing controller
    $router->post('/app/addListing', 'AddEditListingController@addListing');
    $router->post('/app/buildAddListingForm', 'AddEditListingController@buildAddListingForm');
    $router->post('/app/editListingInfo', 'AddEditListingController@editListingInfo');
    $router->post('/app/editListing', 'AddEditListingController@editListing');
    $router->post('/app/uploadMedia', 'AddEditListingController@uploadMedia');
    $router->post('/app/uploadYoutubeMedia', 'AddEditListingController@uploadYoutubeMedia');
    $router->post('/app/isAddListingAllow', 'AddEditListingController@isAddListingAllow');
    $router->post('/app/deleteEventRate', 'AddEditListingController@deleteEventRate');

    // Listings controller
    $router->get('/app/getListingDetails', 'ListingsController@getListingDetails');
    $router->post('/app/getListingsByCategory', 'ListingsController@getListingsByCategory');
    $router->get('/app/getListingsByAccount', 'ListingsController@getListingsByAccount');
    $router->get('/app/myListings', 'ListingsController@myListings');
    $router->get('/app/getListingPlans', 'ListingsController@getListingPlans');
    $router->post('/app/getMyPackages', 'ListingsController@getMyPackages');
    $router->post('/app/getPurchagePackages', 'ListingsController@getPurchagePackages');
    $router->post('/app/upgradeListing', 'ListingsController@upgradeListing');
    $router->post('/app/upgradePackage', 'ListingsController@upgradePackage');
    $router->post('/app/removeListing', 'ListingsController@removeListing');
    $router->get('/app/myFavorites', 'ListingsController@myFavorites');
    $router->get('/app/actionFavorite', 'ListingsController@actionFavorite');
    $router->post('/app/addReportBrokenListing', 'ListingsController@addReportBrokenListing');
    $router->post('/app/removeReportBrokenListing', 'ListingsController@removeReportBrokenListing');
    $router->post('/app/search', 'ListingsController@search');
    $router->post('/app/searchOnMap', 'ListingsController@searchOnMap');
    $router->get('/app/keywordSearch', 'ListingsController@keywordSearch');
    $router->get('/app/smartSearch', 'ListingsController@smartSearch');
    $router->post('/app/saveSearch', 'ListingsController@saveSearch');
    $router->get('/app/getSearchResult', 'ListingsController@getSearchResult');
    $router->post('/app/getSaveAlerts', 'ListingsController@getSaveAlerts');
    $router->post('/app/massSavedSearch', 'ListingsController@massSavedSearch');
    $router->get('/app/getCallOwnerData', 'ListingsController@getCallOwnerData');
    $router->get('/app/makeServiceRequest', 'ListingsController@makeServiceRequest');

    // Comments controller
    $router->get('/app/getCommentsNext', 'CommentsController@getCommentsNext');
    $router->post('/app/addComment', 'CommentsController@addComment');

    // Geo locations controller
    $router->get('/app/geoAutocomplete', 'GeoLocationController@geoAutocomplete');
    $router->get('/app/getAutocompleteDataByKey', 'GeoLocationController@getAutocompleteDataByKey');
    $router->get('/app/getGeoData', 'GeoLocationController@getGeoData');

    // Comments controller
    $router->post('/app/getFilterFields', 'CategoryFilterController@getFilterFields');

    // Messages controller
    $router->get('/app/getContacts', 'MessagesController@getContacts');
    $router->post('/app/getMessages', 'MessagesController@getMessages');
    $router->post('/app/removeContacts', 'MessagesController@removeContacts');
    $router->post('/app/removeMessages', 'MessagesController@removeMessages');
    $router->post('/app/sendMessage', 'MessagesController@sendMessage');
    $router->post('/app/contactOwner', 'MessagesController@contactOwner');
    $router->post('/app/updateMessageStatus', 'MessagesController@updateMessageStatus');

    // PushNotification controller
    $router->get('/app/sendNotify', 'PushNotificationController@sendNotify');

    // Shopping Cart controller
    $router->get('/app/shoppingAction', 'ShoppingCartController@shoppingAction');
    $router->get('/app/getShoppingCartItems', 'ShoppingCartController@getShoppingCartItems');
    $router->post('/app/prepareCartBeforePayment', 'ShoppingCartController@prepareCartBeforePayment');
    $router->get('/app/getMySoldPurchaseItems', 'ShoppingCartController@getMySoldPurchaseItems');
    $router->get('/app/getOrder', 'ShoppingCartController@getOrder');
    $router->get('/app/shoppingCartChangeShippingStatus', 'ShoppingCartController@shoppingCartChangeShippingStatus');

    rl('Hook')->load('apiRegisterRoutesV1', $router, $api_autoload);
});




// Load custom API routes
require_once __DIR__ . '/routes.custom.php';
