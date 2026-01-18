<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: YANDEXGPT.PHP
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

class YandexGPT extends Provider
{
    /**
     * API endpoint url
     * @var string
     */
    protected $endpoint = 'https://llm.api.cloud.yandex.net/foundationModels/v1/completion';

    /**
     * Is provider configured
     *
     * @return boolean - Is configured flag
     */
    public function isConfigured(): bool
    {
        global $config;

        return ($config['openai_yandex_oauth_token'] && $config['openai_yandex_catalog_id']);
    }

    /**
     * Make request to provider API
     *
     * @since 1.1.0 - Return value type changed from string to array
     *
     * @param  string $systemMessage - System role message
     * @param  string $userMessage   - User role message
     * @param  int    $fieldLength   - Field length
     * @return array                 - Ajax response with the generated text
     */
    public function request(string $systemMessage, string $userMessage, int $fieldLength): array
    {
        global $config;

        $max_tokens = round(($fieldLength ?: 2000) * 1.15);

        $data = [
            'modelUri' => sprintf('gpt://%s/yandexgpt-lite', $config['openai_yandex_catalog_id']),
            'completionOptions' => [
                'stream' => false,
                'temperature' => 0.8,
                'maxTokens' => $max_tokens
            ],
            'messages' => [
                [
                    'role' => 'system',
                    'text' => $systemMessage
                ], [
                    'role' => 'user',
                    'text' => $userMessage
                ]
            ]
        ];

        if ($api_token = $this->getAPIToken()) {
            return $this->curlRequest($data, $api_token);
        } else {
            return [
                'status' => 'ERROR',
                'message' => $this->getError()
            ];
        }
    }

    /**
     * Check response data consistency
     *
     * @since 1.1.0 - Return value type changed from string to array
     *
     * @param  array  $response - cURL response
     * @return string           - Ajax response with the generated text
     */
    public function checkResponse(array $response): array
    {
        if ($response
            && isset($response['result'])
            && is_array($response['result']['alternatives'])
            && isset($response['result']['alternatives'][0]['message'])
            && isset($response['result']['alternatives'][0]['message']['text'])
        ) {
            return [
                'status' => 'OK',
                'results' => $response['result']['alternatives'][0]['message']['text']
            ];
        } else {
            $error_message = $response['error'] && $response['error']['message'] ? ' (' . $response['error']['message'] . ')' : '';
            $GLOBALS['rlDebug']->logger('YandexGPT error: No data found in API call response' . $error_message);

            return [
                'status' => 'ERROR',
                'message' => $response['error'] && $response['error']['message']
                    ? $response['error']['message']
                    : $GLOBALS['rlLang']->getSystem('openai_error_empty_response')
            ];
        }
    }

    /**
     * Get temporary API token by constant oauth token
     *
     * @return string - API token
     */
    private function getAPIToken(): string
    {
        global $config;

        $time = (int) $config['openai_yandex_token_time'];

        if ($config['openai_yandex_api_key'] && $time && (time() - $time) < 39600) { // 11 hours
            return $config['openai_yandex_api_key'];
        } else {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://iam.api.cloud.yandex.net/iam/v1/tokens');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, sprintf('{"yandexPassportOauthToken":"%s"}', $config['openai_yandex_oauth_token']));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json'
            ));
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($ch);

            if (curl_error($ch)) {
                $this->setError(curl_error($ch));

                return '';
            } else {
                $data = json_decode($response, true);
                
                if ($data['message']) {
                    $this->setError($data['message']);
                    return '';
                } elseif ($data['iamToken']) {
                    $GLOBALS['rlDb']->update([
                        [
                            'fields' => ['Default' => $data['iamToken']],
                            'where' => ['Key' => 'openai_yandex_api_key'],
                        ], [
                            'fields' => ['Default' => time()],
                            'where' => ['Key' => 'openai_yandex_token_time'],
                        ]
                    ], 'config');

                    return $data['iamToken'];
                } else {
                    $this->setError('No data received for Yandex IAM token request');
                    return '';
                }
            }
        }
    }
}
