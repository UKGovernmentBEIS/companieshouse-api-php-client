<?php
namespace UKGovernmentBEIS\CompaniesHouse;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ClientException;
use http\Exception\BadMessageException;
use http\Exception\InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;


class Client {

    /**
     * @const string Current version of this client.
     * This follows Semantic Versioning (http://semver.org/)
     */
    const VERSION = '2.0.0';

    /**
     * @const string The default API endpoint for Companies House.
     */
    const API_BASE_URL = 'https://api.companieshouse.gov.uk';

    /**
     * @var HttpClientInterface PSR-7 compatible HTTP Client
     */
    private $httpClient;

    /**
     * @var string The API endpoint.
     */
    private $baseUrl;

    /**
     * @var string The API Key.
     */
    private $apiKey;

    /**
     * Create an API client for the given uri endpoint.
     *
     * @param string $apiKey The API key
     */
    public function __construct(string $apiKey = NULL, string $base_url = self::API_BASE_URL)
    {
        if (filter_var($base_url, FILTER_VALIDATE_URL) === false ) {
            throw new InvalidArgumentException(
                "Invalid 'base_url' set. This must be a valid URL or null."
            );
        }
        $this->baseUrl = $base_url;
        $this->apiKey = $apiKey;

        if (!isset($this->httpClient)) {
            $this->httpClient = new HttpClient([
                'base_uri' => $this->baseUrl,
                'auth' => [$this->apiKey, ''],
                'headers' => [
                    'exceptions' => false,
                    'allow_redirects' => false,
                ]
            ]);
        }

        return $this->httpClient;
    }

    //-------------------------------------------
    // API methods - https://developer-specs.company-information.service.gov.uk/api.ch.gov.uk-specifications/swagger-2.0/spec/swagger.json

    /**
     * Registered Office Address
     *
     * https://developer-specs.company-information.service.gov.uk/companies-house-public-data-api/reference/registered-office-address/registered-office-address
     *
     * @return array|null
     */
    public function registeredOfficeAddress(string $companyNumber) {
        try {
            $response = $this->client()
                ->get("/company/{$companyNumber}/registered-office-address");
        }
        catch (BadResponseException $e) {
            $response = $e->getResponse();
            return $this->handleException($response);
        }

        return $this->handleResponse($response);
    }

    /**
     * Company profile
     *
     * https://developer-specs.company-information.service.gov.uk/companies-house-public-data-api/reference/company-profile/company-profile
     *
     * @param $companyNumber
     *   The company number being requested
     *
     * @return array|null
     */
    public function companyProfile(string $companyNumber) {
        try {
            $response = $this->client()
                ->get("/company/{$companyNumber}");
        }
        catch (BadResponseException $e) {
            $response = $e->getResponse();
            return $this->handleException($response);
        }

        return $this->handleResponse($response);
    }

    /**
     * Search
     *
     * https://developer-specs.company-information.service.gov.uk/companies-house-public-data-api/reference/search/search-all
     *
     * @param $q
     *   The query to search for.
     * @param int $itemsPerPage
     *   Optional. The number of officers to return per page.
     * @param int $startIndex
     *   Optional. The offset into the entire result set that this page starts.
     *
     * @return array|null
     */
    public function searchAll(string $q, int $items_per_page = 10, int $start_index = 0) {
        try {
            $response = $this->client()
                ->get("/search", [
                    'query' => array_filter([
                        'q' => $q,
                        'items_per_page' => $items_per_page,
                        'start_index' => $start_index,
                    ])
                ]);
        }
        catch (BadResponseException $e) {
            $response = $e->getResponse();
            return $this->handleException($response);
        }

        return $this->handleResponse($response);
    }

    /**
     * Search
     *
     * https://developer-specs.company-information.service.gov.uk/companies-house-public-data-api/reference/search/search-all
     *
     * @param string $q
     *   The search term.
     * @param int $items_per_page
     *   Optional. The number of officers to return per page.
     * @param int $start_index
     *   Optional. The offset into the entire result set that this page starts.
     * @param ?string $restrictions
     *   Optional. Enumerable options to restrict search results. Space separate multiple restriction options to combine
     *   functionality. For a "company name availability" search use "active-companies legally-equivalent-company-name" together.
     *
     * @return array|null
     */
    public function searchCompanies(string $q, int $items_per_page = 10, int $start_index = 0, string $restrictions = null) {
        try {
            $response = $this->client()
                ->get("/search/companies", [
                    'query' => array_filter([
                        'q' => $q,
                        'items_per_page' => $items_per_page,
                        'start_index' => $start_index,
                        'restrictions' => $restrictions,
                    ])
                ]);
        }
        catch (BadResponseException $e) {
            $response = $e->getResponse();
            return $this->handleException($response);
        }

        return $this->handleResponse($response);
    }

    //-----------------------------------
    // Http client helpers.

    /**
     * Returns the GuzzleClient.
     *
     * @return GuzzleClient
     */
    private function client() {
        return $this->httpClient;
    }

    /**
     * Handle the API response.
     *
     * @param ResponseInterface $response
     *   The response object.
     *
     * @return array
     *   The response body returned from a successful request.
     *
     * @throws ApiException|NotFoundException|UnauthorisedException
     *   Known API exception errors.
     * @throws ClientException
     *   Unknown client errors, including rate limiting errors.
     *
     * @return mixed
     *   The decoded json response.
     */
    private function handleResponse($response) {
        $json = json_decode($response->getBody(), true);
        $body = $json ?? $body;

        // Data is mostly returned as JSON documents.
        if (!is_array($body)) {
            throw new BadMessageException('Malformed JSON response from server', $response->getStatusCode(), $body, $response);
        }

        if ($response->getStatusCode() === 200) {
            return $body;
        }
        else {
            return $this->handleException($response);
        }
    }

    /**
     * Handle the API exception.
     *
     * @param ResponseInterface $response
     *   The response object.
     *
     * @return array
     *   The response body returned from a successful request.
     *
     * @throws ApiException|NotFoundException|UnauthorisedException
     *   Known API exception errors.
     * @throws ClientException
     *   Unknown client errors.
     *
     * @return null
     *   Do not return anything.
     */
    private function handleException($response) {
        switch($response->getStatusCode()){
            case 401:
                throw new UnauthorisedException($response->getMessage(), $response->getStatusCode(), $response);
            case 404:
                throw new NotFoundException($response->getMessage(), $response->getStatusCode(), $response);
            case 429:
                throw new RateLimitException($response->getMessage(), $response->getStatusCode(), $response);
            default:
                throw new ApiException($response->getMessage(), $response->getStatusCode(), $response);
        }

        return NULL;
    }

}
