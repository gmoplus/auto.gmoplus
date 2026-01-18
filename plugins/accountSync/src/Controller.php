<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.7.0
 *	LICENSE: FL0F971OQTZ9 - https://www.flynax.com/license-agreement.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN: gmowin.com
 *	FILE: CONTROLLER.PHP
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

namespace Flynax\Plugins\AccountSync;

use Flynax\Plugins\AccountSync\Http\Request;
use Flynax\Plugins\AccountSync\Http\Response;
use Flynax\Plugins\AccountSync\Models\Token;

class Controller
{
    /**
     * @var \rlDb
     */
    protected $rlDb;

    /**
     * @var \rlValid
     */
    protected $rlValid;

    /**
     * @var \Flynax\Plugins\AccountSync\Http\Request
     */
    protected $request;

    /**
     * @var mixed - Provided data with request
     */
    protected $requestData;

    /**
     * Controller constructor.
     */
    public function __construct()
    {
        $this->rlDb = asMake('rlDb');
        $this->rlValid = asMake('rlValid');

        $request = new Request();
        $this->request = $request;
        $this->requestData = $request->getParams();
    }

    /**
     * Validate request by rules
     *
     * @param array $rules - Validation rules
     */
    public function validate($rules)
    {
        $errors = [];

        foreach ($rules as $field => $rule) {
            if (!$this->requestData[$field]) {
                $errors[] = sprintf("'%s' is required, please send it", $field);
            }
        }

        if (!empty($errors)) {
            Response::error($errors, Response::BAD_REQUEST, 'fields_required');
            die();
        }
    }

    /**
     * Check does request sent necessary authorization data like token and domain
     *
     * @return bool
     */
    private function isAuth()
    {
        $tokenManager = new Token();
        $token = $this->requestData['token'];

        return $tokenManager->isTokenExist($token);
    }

    /**
     * Should request by authenticated
     */
    public function shouldAuth()
    {
        if (!$this->isAuth()) {
            Response::error('Not Authorized', Response::UNAUTHORIZED, 'not_authorized');
            die();
        }
    }
}
