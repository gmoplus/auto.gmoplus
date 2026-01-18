<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: PROVIDER.PHP
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

namespace Flynax\Plugins\OpenAI\Providers;

abstract class Provider
{
    /**
     * Last error message
     *
     * @since 1.1.0
     * @var string
     */
    protected $lastError = '';

    /**
     * Endpoint API URL
     * @var string
     */
    protected $endpoint = '';

    /**
     * Check response data consistency
     *
     * @since 1.1.0 - Return value type changed from string to array
     *
     * @param  array $response - cURL response
     * @return array           - Ajax response with the generated text
     */
    protected function checkResponse(array $response): array
    {
        return [];
    }

    /**
     * Write error to logs
     *
     * @param string $logError - Error message
     */
    protected function setError(string $logError)
    {
        $this->lastError = $logError;
        $GLOBALS['rlDebug']->logger('OpenAI error: ' . $logError);
    }

    /**
     * Get the latest error message
     *
     * @since 1.1.0
     *
     * @return string - Last error message
     */
    public function getError(): string
    {
        return $this->lastError;
    }

    /**
     * Make cURL request to the provider API
     *
     * @since 1.1.0 - Return value type changed from string to array
     *
     * @param  array  $data   - API parameters array
     * @param  string $apiKey - API key
     * @return array          - Ajax response with generated text string
     */
    protected function curlRequest(array $data, string $apiKey): array
    {
        if (!extension_loaded('curl')) {
            $this->setError('No cURL php extension loaded');

            return [
                'status' => 'ERROR',
                'message' => $GLOBALS['rlLang']->getSystem('openai_error_curl_required')
            ];
        }

        $json_data = json_encode($data);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);

        if (curl_error($ch)) {
            $this->setError(curl_error($ch));

            return [
                'status' => 'ERROR',
                'message' => curl_error($ch)
            ];
        } else {
            $response = json_decode($response, true);
            return $this->checkResponse($response);
        }
    }
}
