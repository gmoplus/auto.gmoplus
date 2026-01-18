<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : RLAPPLEPUSH.CLASS.PHP
 *
 *	This script is a commercial software and any kind of using it must be
 *	coordinate with Flynax Owners Team and be agree to Flynax License Agreement
 *
 *	This block may not be removed from this file or any other files with out
 *	permission of Flynax respective owners.
 *
 *	Copyrights Flynax Classifieds Software | 2023
 *	https://www.flynax.com
 *
 ******************************************************************************/

class rlApplePush
{
    /**
     * Sandboxing
     * 
     * Set FALSE for prodaction or TestFlight app's
     */
    private $sandbox = false;

    /**
     * @since 3.4.0
     * @var resource
     */
    private static $apns_socket = null;

    /**
     * @since 3.4.0
     * @var bool
     */
    protected $apns_close_manually = false;

    /**
     * APNS server
     */
    private $apns_server = null;

    /**
     * @since 3.4.0
     */
    const PUSH_TYPE_SAVED_SEARCH = 'saved_search';
    const PUSH_TYPE_MESSAGES = 'messages';
    const PUSH_TYPE_COMMENTS = 'comments';
    const PUSH_TYPE_NEWS = 'news';

    /**
     * Convert types: str2int
     */
    protected $notify_types = array(
        self::PUSH_TYPE_SAVED_SEARCH => 1,
        self::PUSH_TYPE_MESSAGES => 2,
        self::PUSH_TYPE_COMMENTS => 3,
        self::PUSH_TYPE_NEWS => 4,
    );

    /**
     * Connect to APNS server timeout
     */
    public $timeout = 10;

    /**
     * APNS certificate
     */
    private $apns_cert = null;

    /**
     * Private key's passphrase
     */
    private $passphrase = '';

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->setupAPNSServer();
    }

    /**
     * @since 3.1.1
     */
    private function setupAPNSServer()
    {
        $this->apns_cert = RL_PLUGINS . 'iFlynaxConnect' . RL_DS . 'cert' . RL_DS;

        if ($this->sandbox) {
            $this->apns_server = 'https://api.development.push.apple.com/3/device/';
            $this->apns_cert .= 'apns-dev-cert.pem';
        } else {
            $this->apns_server = 'https://api.push.apple.com/3/device/';
            $this->apns_cert .= 'apns-prod-cert.pem';
        }
    }

    /**
     * @since 3.1.1
     */
    public function enableDevMode()
    {
        $this->sandbox = true;
        $this->setupAPNSServer();
    }

    /**
     * Generate payload body
     *
     * @param int $type - messages,news,etc..
     * @param mixed $user_data - any additional information required for the push
     * @param int $badge - increase badge counter on device with the number
     */
    public function generatePayloadBody($type = null, $user_data = array(), $badge = 0)
    {
        // send only registered action types
        if (false === $action = $this->notify_types[$type]) {
            throw new InvalidArgumentException("Unknown notify type: '{$type}'");
        }

        // prepare payload body
        $body = array(
            'aps' => array(
                'sound' => 'default',
                'alert' => $user_data['message'],
                'badge' => intval($badge),
                'action' => intval($action),
                'info' => $user_data,
             )
        );
        return $body;
    }

    /**
     * Generate alert source with key and arguments
     *
     * @since 3.4.0
     *
     * @var string $key  - Phrase key for the PUSH
     * @var array  $args - Some additional data to interpolate the PUSH message
     * @return array
     */
    public function generateAlertSource($key, $args = null)
    {
        $sql = "SELECT `Code`, `Value` FROM `{db_prefix}iflynax_phrases` ";
        $sql .= "WHERE `Key` = '{$key}'";
        $values = $GLOBALS['rlDb']->getAll($sql);

        $source = array(
            'loc-key' => $key,
            'loc-values' => $values,
        );

        if (is_array($args) && !empty($args)) {
            $source['loc-args'] = $args;
        }
        return $source;
    }

    /**
     * Generate localized alert
     * 
     * @since 3.1.1
     * @param array $source - some data for the alert
     * @example
     *      'loc-key' => 'notification_new_message_by',
     *      'loc-args' => array(
     *          '{name}' => $name
     *      )
     * @return string
     */
    public function generateLocalizedAlert($source)
    {
        if (!is_array($source)) {
            throw new InvalidArgumentException('Argument "source" must be array');
        } elseif (!isset($source['loc-key'])) {
            throw new InvalidArgumentException('Argument "source" must have: loc-key');
        }
        $out = array();

        if (empty($source['loc-values'])) {
            throw new Exception(sprintf('Not found alert phrases by key "%s"', $source['loc-key']));
        }

        foreach ($source['loc-values'] as $entry) {
            $_code = $entry['Code'];

            if ($entry['Value'] == '') {
                $out[$_code] = $source['loc-key'];
                continue;
            }

            if (isset($source['loc-args'])) {
                $out[$_code] = str_replace(
                    array_keys($source['loc-args']),
                    array_values($source['loc-args']),
                    $entry['Value']
                );
            } else {
                $out[$_code] = $entry['Value'];
            }
        }
        return $out;
    }

    /**
     * @deprecated 3.7.3
     * @since 3.1.1
     */
    private function _buildBinaryNotificationForDevice($device, $payload, $alert)
    {
        $token = $device['Token'];
        $language = $device['Language'];
        $payload['aps']['alert'] = $alert[$language];
        $payload_json = json_encode($payload);

        return chr(0) . pack('n', 32) . pack('H*', $token) . pack('n', strlen($payload_json)) . $payload_json;
    }

    /**
     * Send notifications trougth APNS
     *
     * @param array $tokens - device tokens
     * @param array $payload
     * @param array $alert
     *
     * @see rlIFlynaxConnect::fetchAllActiveTokensByAccountId
     * @see self::generatePayloadBody
     * @see rlIFlynaxConnect::generateLocalizedAlert
     */
    public function pushNotifications($tokens, $payload, $alert)
    {
        global $config;

        if (!is_array($tokens)) {
            throw new InvalidArgumentException('Tokens must be Array');
        } elseif (empty($tokens)) {
            throw new LengthException('Tokens is empty');
        } elseif (!is_array($payload)) {
            throw new InvalidArgumentException('Payload must be Array');
        } elseif (empty($payload)) {
            throw new LengthException('Payload is empty');
        } elseif (!file_exists($this->apns_cert)) {
            throw new Exception('PEM file does not exists; double check it.');
        } elseif (!extension_loaded('curl')) {
            throw new Exception('CURL library is not available');
        } elseif (!$config['iflynax_bundle_identifier']) {
            throw new Exception('Bundle Indentifier is not specified');
        }

        $apns_topic = $config['iflynax_bundle_identifier'];

        if ($alert) {
            $message = $payload['aps']['alert'];
            $title = $alert[$config['lang']];

            if ($recipient_id = $payload['aps']['info']['recipientID']) {
                $preferred_lang = $GLOBALS['rlDb']->getOne('Lang', "`ID` = {$recipient_id}", 'accounts');
                $title = $alert[$preferred_lang] ?: $title;
            }

            $payload['aps']['alert'] = [
                'title' => $title,
                'body' => $message
            ];
        }

        foreach ($tokens as $entry) {
            $ch = curl_init($this->apns_server . $entry['Token']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('apns-topic: ' . $apns_topic));
            curl_setopt($ch, CURLOPT_SSLCERT, $this->apns_cert);
            curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $this->passphrase);
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            $result = curl_exec($ch);
            curl_close($ch);

            if (!$result) {
                throw new Exception('Unable to send push, cURL request failed');
            }
        }
    }

    /**
     * @since 3.4.0
     */
    public function shouldCloseConnectionManually()
    {
        $this->apns_close_manually = true;
    }

    /**
     * Close opened socket connection with APNS
     *
     * @since 3.4.0
     */
    public function closeConnection()
    {
        if ($this->apns_socket) {
            @fclose($this->apns_socket);
            $this->apns_socket = null;
        }
    }
}
