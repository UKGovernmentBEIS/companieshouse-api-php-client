<?php
namespace UKGovernmentBEIS\CompaniesHouse;

use Psr\Http\Message\ResponseInterface;

class ApiException extends \RuntimeException {

    /**
     * An API response.
     *
     * @var ResponseInterface
     */
    private ResponseInterface $response;

    /**
     * The body of the API response.
     *
     * @var mixed
     */
    private mixed $body;

    public function __construct(string $message, int $code, ResponseInterface $response) {
        $this->response = $response;

        // Attempt to decode the message body.
        if ($response && $body = $response->getBody()) {
          $json = json_decode($body);
          $this->body = $json ?? $body;
        }

        parent::__construct($message, $code);
    }

    /**
     * Returns the full response the lead to the exception.
     *
     * @return ResponseInterface
     */
    public function getResponse(){
        return $this->response;
    }

    /**
     * Returns the body of the response.
     *
     * @return mixed
     *   The body of the response if set.
     */
    public function getBody(){
        return $this->body;
    }

}
