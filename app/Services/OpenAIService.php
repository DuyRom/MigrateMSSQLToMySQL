<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class OpenAIService
{
    protected $client;
    protected $apiKey;
    protected $model;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = config('services.openai.api_key');
        $this->model = config('services.openai.model');
    }

    /**
     * Converts an MSSQL stored procedure definition to MySQL syntax using the OpenAI API.
     *
     * @param string $procedureDefinition The definition of the MSSQL stored procedure.
     * @return string The converted MySQL syntax as a single SQL string suitable for use with $pdo->exec().
     * @throws \Exception If there is an error with the OpenAI API or network.
     */
    public function convertProcedure($procedureDefinition)
    {
        $prompt = "Convert the following MSSQL stored procedure to MySQL. Only return the SQL syntax without any explanations or comments. Ensure the output is a single SQL string suitable for use with \$pdo->exec():\n\n" . $procedureDefinition;

        try {
            $response = $this->client->post('https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            $convertedProcedure = $data['choices'][0]['message']['content'] ?? 'No response';

            $convertedProcedure = preg_replace('/DELIMITER\s+\S+\s*/', '', $convertedProcedure);
            $convertedProcedure = str_replace(['//', '$'], '', $convertedProcedure);

            $convertedProcedure = preg_replace('/\bdbo\./i', '', $convertedProcedure);

            return $convertedProcedure;
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $statusCode = $response->getStatusCode();
                $body = json_decode($response->getBody()->getContents(), true);

                if ($statusCode == 401) {
                    throw new \Exception('Invalid OpenAI API key.');
                }

                throw new \Exception('Error from OpenAI API: ' . ($body['error']['message'] ?? 'Unknown error'));
            } else {
                throw new \Exception('Network error or no response from OpenAI API.');
            }
        }
    }
}
