<?php
/**copyright**/

namespace Flynax\Plugins\CarSpecs\Modules;

use Flynax\Plugins\CarSpecs\HTTP\Request;
use Flynax\Plugins\CarSpecs\Interfaces\ModuleInterface;

/**
 * Class Vinaudit
 *
 * @since 2.1.0
 * @package Flynax\Plugins\CarSpecs\Modules
 */
class Vinaudit implements ModuleInterface
{
    /**
     * @var \Flynax\Plugins\CarSpecs\HTTP\Request
     */
    private $httpClient;

    /**
     * @var string - Base url To the VinAudit API
     */
    private $baseUri = 'https://api.vinaudit.com/';

    /**
     * @var bool - Should I enable debug mode
     */
    public $enableDebug = true;

    /**
     * @var string - VinAudit API Key
     */
    public $apiKey;

    /**
     * @var string - VinAudit user
     */
    public $apiUser;

    /**
     * @var string - VinAudit password
     */
    public $apiPass;

    /**
     * @var array - API Errors
     */
    public $errors;

    /**
     * @var string - Path where all PDF reports will be located
     */
    public $pdfFolder = RL_FILES . 'vin-audit-reports/';

    /**
     * VinAudit constructor.
     *
     * @param string $key  - API Key
     * @param string $user - API username/email
     * @param string $pass - API Password
     */
    public function __construct($key = '', $user = '', $pass = '')
    {
        if ($key && $user && $pass) {
            $this->apiUser = $user;
            $this->apiPass = $pass;
            $this->apiKey = $key;
        } else {
            $this->fetchAPICredentials();
        }

        $client = new Request();
        $client->setBaseURI($this->baseUri);

        $this->httpClient = $client;
    }

    /**
     * Fetch all data from the first 'VinAudit' service and fill all necessary credentials
     */
    public function fetchAPICredentials()
    {
        $sql = "SELECT * FROM `" . RL_DBPREFIX . "car_specs_services` WHERE ";
        $sql .= "`Login` != '' AND `Pass` != '' AND `Api_key` !='' AND `Module` = 'vinaudit.php'";
        $serviceInfo = $GLOBALS['rlDb']->getRow($sql);

        $this->apiUser = $serviceInfo['Login'];
        $this->apiPass = $serviceInfo['Pass'];
        $this->apiKey = $serviceInfo['Api_key'];;
    }


    /**
     * Get car information by unique identification: Car Plate or Vin number
     *
     * @param string $carIdentifier
     * @return mixed
     */
    public function getCarInfo($carIdentifier)
    {
        // TODO: Implement getCarInfo() method.
    }

    /**
     * Get short information about car using VIN
     *
     * @param string $vin
     *
     * @return mixed
     */
    public function parseVin($vin)
    {
        $result = $this->httpClient->send('query.php', array(
            'vin' => $vin,
            'mode' => $this->enableDebug ? 'test' : '',
            'key' => $this->apiKey,
        ));

        return $result;
    }

    /**
     * Saving Report as PDF file to the 'files' folder
     *
     * @param string $vin
     *
     * @return bool
     */
    public function savePDF($vin)
    {
        global  $lang;

        $queryResult = $this->parseVin($vin);
        $filePath = "{$this->pdfFolder}{$vin}.pdf";

        if (file_exists($filePath)) {
            return $filePath;
        }

        if (!is_dir($this->pdfFolder)) {
            $GLOBALS['reefless']->rlMkdir($this->pdfFolder);
        }

        if (!file_exists($filePath)) {
            if ($queryResult->success) {
                $id = $queryResult->id;
                $this->httpClient->download('pullreport.php', array(
                    'id' => $id,
                    'vin' => $vin,
                    'pdf' => 1,
                    'user' => $this->apiUser,
                    'pass' => $this->apiPass,
                    'mode' => $this->enableDebug ? 'test' : '',
                    'key' => $this->apiKey,
                ), $filePath);

                return $filePath;
            }

            $this->errors[] = $lang['cs_nothing_found_by_vin'];
            return false;
        }

        $this->errors[] = $lang['cs_something_went_wrong'];
        return false;
    }
}
