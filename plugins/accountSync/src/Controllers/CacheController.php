<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.7.0
 *	LICENSE: FL0F971OQTZ9 - https://www.flynax.com/license-agreement.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN: gmowin.com
 *	FILE: CACHECONTROLLER.PHP
 *
 *	The software is a commercial product delivered under single, non-exclusive,
 *	non-transferable license for one domain or IP address. Therefore distribution,
 *	sale or transfer of the file in whole or in part without permission of Flynax
 *	respective owners is considered to be illegal and breach of Flynax License End
 *	User Agreement.
 *
 *	You are not allowed to remove this information from the file without permission
 *	of Flynax respective owners.
 *
 *	Flynax Classifieds Software 2022 |  All copyrights reserved.
 *
 *	https://www.flynax.com/
 *
 ******************************************************************************/

namespace Flynax\Plugins\AccountSync\Controllers;

use Flynax\Plugins\AccountSync\Adapters\AccountTypesAdapter;
use Flynax\Plugins\AccountSync\Controller;
use Flynax\Plugins\AccountSync\Http\Response;
use Flynax\Plugins\AccountSync\Models\MetaData;

class CacheController extends Controller
{
    /**
     * Update account types cache of the specific domain
     */
    public function updateAccountTypes()
    {
        // enable auth
        $this->shouldAuth();

        $this->validate(array(
            'account_types' => 'required',
            'domain' => 'required',
        ));

        $types = $this->requestData['account_types'];
        $domain = $this->requestData['domain'];
        $meta = new MetaData();

        if ($meta->set($domain, 'account_types', $types)) {
            $ourTypes = AccountTypesAdapter::getAllTypes();
            $sendData = array(
                'domain' => RL_URL_HOME,
                'account_types' => $ourTypes,
            );

            Response::success($sendData, Response::SUCCESS, 'exchange_success');
        }

        Response::error(asLang('as_something_wrong'), Response::SERVER_ERROR, 'exchange_fail');
    }
}
