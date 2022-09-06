<?php

namespace App\Http;

use Exception;
use App\Http\Response;

class Request
{
    //constants for methods
    public const GET = 'GET';
    public const POST = 'POST';
    public const DELETE = 'DELETE';
    public const PATCH = 'PATCH';
    public const PUT = 'PUT';
    public const OPTIONS = 'OPTIONS';
    public const HEAD = 'HEAD';
    
    //constants for errors
    public const INTERNAL_ERROR = 500;

    private static $instance;

    public function makeRequest(string $method, string $url, array $body = [], array $headers = [])
    {
        return $this->sendRequest(strtoupper($method), $url, $body, $headers);
    }

    //make appropriate URL
    protected function createURL(string $method, string $url, array $body = [])
    {
        $method = strtoupper($method);

        switch ($method) {
            case self::HEAD:
            case self::GET:
                if (is_array($body)) {
                    if (strpos($url, '?') !== false) {
                        $url .= '&';
                    } else {
                        $url .= '?';
                    }

                    $url .= urldecode(http_build_query($body));
                }
                break;
        }

        return $url;
    }

    //create request structure
    protected function requestStructure(string $method, array $body = [], array $headers = [])
    {
        $payload = '';
        $method = strtoupper($method);
        
        $headers = array_change_key_case($headers, CASE_LOWER);

        switch ($method) {
            case self::DELETE:
            case self::POST:
            case self::PUT:
            case self::OPTIONS:
            case self::PATCH:
                if (is_array($body)) {
                    if (!empty($headers['content-type'])) {
                        switch (trim($headers['content-type'])) {
                            case 'application/x-www-form-urlencoded':
                                $body = http_build_query($body);
                                break;
                            case 'application/json':
                                $body = json_encode($body);
                                break;
                        }
                    } else {
                        $headers['content-type'] = 'application/json';
                        $body = json_encode($body);
                    }
                } elseif (empty($headers['content-type'])) {
                    $headers['content-type'] = 'application/json';
                    $body = json_encode($body);
                }

                $payload = $body;
                break;
        }

        $structure = [
            'http' => [
                'method' => $method,
            ],
        ];

        if ($headers) {
            $structure['http']['header'] = implode(
                "\r\n",
                array_map(
                    function ($value, $key) {
                        return sprintf("%s: %s", $key, $value);
                    },
                    $headers,
                    array_keys($headers)
                )
            );
        }

        if ($payload) {
            $structure['http']['content'] = $payload;
        }

        return $structure;
    }

    //Send request
    private function sendRequest(string $method, string $url, array $body = [], array $headers = [])
    {
        $url = $this->createURL($method, $url, $body);

        $structure = $this->requestStructure($method, $body, $headers);

        $streamContext = stream_context_create($structure);

        $response = file_get_contents($url, false, $streamContext);

        if ($response === false) {
            $statusLine = implode(',', $http_response_header);
            preg_match('{HTTP\/\S*\s(\d{3})}', $statusLine, $match);
            $status = $match[1];
            $exceptionCode = self::INTERNAL_ERROR;

            if ($status && http_response_code($status)) {
                $exceptionCode = $status;
            }

            if ($this->hasError($status)) {
                throw new Exception(
                    "Invalid response status: {$status}, Request URL: {$url}, " . $statusLine,
                    $exceptionCode
                );
            }
        }

        return new Response($response, $http_response_header);
    }

    private function hasError($status)
    {
        return strpos($status, '2') !== 0 && strpos($status, '3') !== 0;
    }

    public static function getInstance(bool $createNew = false)
    {
        if ($createNew) {
            return new static();
        }

        if (!isset(self::$instance)) {
            self::$instance = new static();
        }

        return self::$instance;
    }
}
