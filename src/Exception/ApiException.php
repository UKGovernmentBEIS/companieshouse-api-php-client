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


    public function __construct(string $message, int $code, ResponseInterface $response) {
        $this->response = $response;
        parent::__construct($this->message, $code);
    }

    /**
     * Returns the full response the lead to the exception.
     *
     * @return ResponseInterface
     */
    public function getResponse(){
        return $this->response;
    }

}
