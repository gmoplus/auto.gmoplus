<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: DEEPSEEK.PHP
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

/**
 * @since 1.1.0
 */
class DeepSeek extends Provider
{
    /**
     * API endpoint url
     * @var string
     */
    protected $endpoint = 'https://api.deepseek.com/chat/completions';

    /**
     * Is provider configured
     *
     * @return boolean - Is configured flag
     */
    public function isConfigured(): bool
    {
        return !!$GLOBALS['config']['openai_deepseek_api_key'];
    }

    /**
     * Make request to provider API
     *
     * @param  string $systemMessage - System role message
     * @param  string $userMessage   - User role message
     * @param  int    $fieldLength   - Field length
     * @return array                 - Ajax response with the generated text
     */
    public function request(string $systemMessage, string $userMessage, int $fieldLength): array
    {
        $max_tokens = round((($fieldLength ?: 2000) - 100) / 4);

        $data = [
            'model' => 'deepseek-chat',
            'messages' => [
                [
                  'role' => 'system',
                  'content' => $systemMessage
                ], [
                  'role' => 'user',
                  'content' => $userMessage
                ]
            ],
            'temperature' => 1,
            'max_tokens' => $max_tokens,
            'top_p' => 1,
            'frequency_penalty' => 0,
            'presence_penalty' => 0
        ];

        return $this->curlRequest($data, $GLOBALS['config']['openai_deepseek_api_key']);
    }

    /**
     * Check response data consistency
     *
     * @param  array $response - cURL response
     * @return array           - Ajax response with the generated text
     */
    public function checkResponse(array $response): array
    {
        if ($response
            && isset($response['choices'])
            && is_array($response['choices'])
            && isset($response['choices'][0]['message'])
            && isset($response['choices'][0]['message']['content'])
        ) {
            return [
                'status' => 'OK',
                'results' => $response['choices'][0]['message']['content']
            ];
        } else {
            $error_message = $response['error'] && $response['error']['message'] ? ' (' . $response['error']['message'] . ')' : '';
            $GLOBALS['rlDebug']->logger('DeepSeek error: No data found in API call response:' . $error_message);

            return [
                'status' => 'ERROR',
                'message' => $response['error'] && $response['error']['message']
                    ? $response['error']['message']
                    : $GLOBALS['rlLang']->getSystem('openai_error_empty_response')
            ];
        }
    }
}
