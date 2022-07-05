<?php
namespace UKGovernmentBEIS\CompaniesHouse;

use GuzzleHttp\Client as HttpClient;
use http\Exception\BadMessageException;
use http\Exception\InvalidArgumentException;


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
        $response = $this->client()
            ->get("/company/{$companyNumber}/registered-office-address");

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
        $response = $this->client()
            ->get("/company/{$companyNumber}");

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
    public function searchAll(string $q, int $items_per_page = NULL, int $start_index = NULL) {
        $response = $this->client()
            ->get("/search", [
                'query' => array_filter([
                    'q' => $q,
                    'items_per_page' => $items_per_page,
                    'start_index' => $start_index,
                ])
            ]);

        return $this->handleResponse($response);
    }

    /**
     * Search
     *
     * https://developer-specs.company-information.service.gov.uk/companies-house-public-data-api/reference/search/search-all
     *
     * @param string $q
     *   The search term.
     * @param int|null $items_per_page
     *   Optional. The number of officers to return per page.
     * @param int|null $start_index
     *   Optional. The offset into the entire result set that this page starts.
     * @param string|null $restrictions
     *   Optional. Enumerable options to restrict search results. Space separate multiple restriction options to combine
     *   functionality. For a "company name availability" search use "active-companies legally-equivalent-company-name" together.
     *
     * @return array|null
     */
    public function searchCompanies(string $q, int $items_per_page = null, int $start_index = null, string $restrictions = null) {
        $response = $this->client()
            ->get("/search", [
                'query' => array_filter([
                    'q' => $q,
                    'items_per_page' => $items_per_page,
                    'start_index' => $start_index,
                    'restrictions' => $restrictions,
                ])
            ]);

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
     * @param $response
     * @return array|null
     */
    private function handleResponse($response) {
        switch($response->getStatusCode()){
            case 200:
                $body = json_decode($response->getBody(), true);

                // The expected response should always be JSON array.
                if(!is_array($body)){
                    throw new BadMessageException( 'Malformed JSON response from server', $response->getStatusCode(), $body, $response );
                }

                return $body;
            case 404:
                return null;
            default:
                return "HTTP ERROR:{$response->getStatusCode()}";
        }
    }

}
