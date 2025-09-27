<?php
class GeminiAPI
{
    private $api_key;
    private $pdo;
    private $settingsController;

    public function __construct($api_key, $pdo = null)
    {
        $this->api_key = $api_key;
        $this->pdo = $pdo;

        if ($pdo) {
            require_once 'Api/Controllers/SettingsController.php';
            $this->settingsController = new SettingsController($pdo);
        }
    }

    /**
     * Get the API key to use (either from database or fallback to constructor key)
     */
    private function getApiKey()
    {
        // Try to get from database first
        if ($this->settingsController) {
            $dbKey = $this->settingsController->getActiveApiKey();
            if ($dbKey) {
                return $dbKey;
            }
        }

        // Fallback to constructor key
        return $this->api_key;
    }

    /**
     * Get the model name from settings
     */
    private function getModelName()
    {
        if ($this->settingsController) {
            $model = $this->settingsController->getSetting('gemini_model');
            if ($model && !empty($model)) {
                return $model;
            }
        }

        return 'gemini-1.5-flash'; // Default model
    }

    /**
     * Make API request with retry logic using different keys
     */
    public function soruSor($prompt)
    {
        $maxRetries = 3;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            $api_key = $this->getApiKey();
            $model = $this->getModelName();

            $result = $this->makeRequest($prompt, $api_key, $model);

            if ($result !== false) {
                // Success - update usage if we have database access
                if ($this->settingsController && $api_key !== $this->api_key) {
                    $this->settingsController->updateApiKeyUsage($api_key);
                }
                return $result;
            }

            // If we have database access, try to mark current key as failed and get a new one
            if ($this->settingsController && $api_key !== $this->api_key) {
                // Log the failure but don't disable the key automatically
                error_log("API key failed on attempt " . ($attempt + 1) . ": " . substr($api_key, 0, 8) . "...");
            }

            $attempt++;

            // Brief delay before retry
            if ($attempt < $maxRetries) {
                usleep(500000); // 0.5 second delay
            }
        }

        error_log("All API key attempts failed after $maxRetries retries");
        return false;
    }

    /**
     * Make the actual API request
     */
    private function makeRequest($prompt, $api_key, $model)
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . $api_key;

        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 30 second timeout

        // SSL settings for local development
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false) {
            error_log('Curl error: ' . curl_error($ch));
            curl_close($ch);
            return false;
        }

        curl_close($ch);

        // Check for rate limiting or API errors
        if ($http_code === 429) {
            error_log("Rate limit exceeded for API key: " . substr($api_key, 0, 8) . "...");
            return false;
        }

        if ($http_code !== 200) {
            error_log("API request failed with HTTP code $http_code: $response");
            return false;
        }

        $result = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('JSON decode error: ' . json_last_error_msg());
            return false;
        }

        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            return $result['candidates'][0]['content']['parts'][0]['text'];
        }

        // Check for API errors in response
        if (isset($result['error'])) {
            error_log('API error: ' . json_encode($result['error']));
            return false;
        }

        error_log('API response format error: ' . print_r($result, true));
        return false;
    }
}
