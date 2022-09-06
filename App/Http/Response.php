<?php

namespace App\Http;

use Exception;

class Response
{
    private $response;
    private $headers;

    public function __construct($response, array $headers = [])
    {
        $this->response = $response;
        $this->headers = $headers;
    }

    public function getResponseBody()
    {
        if (strpos(strtolower(implode(', ', $this->getResponseHeaders())), 'application/json') !== false) {
            $result = json_decode($this->response, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $result;
            } else {
                throw new Exception("Error in JSON decoding: " . json_last_error());
            }
        }

        return $this->response;
    }

    public function getResponseHeaders()
    {
        return $this->headers;
    }
}
