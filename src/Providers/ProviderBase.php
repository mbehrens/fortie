<?php namespace Wetcat\Fortie\Providers;

/*

   Copyright 2015 Andreas Göransson

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.

*/


use Wetcat\Fortie\Exceptions\MissingRequiredAttributeException;
use Wetcat\Fortie\Exceptions\FortnoxException;
use Wetcat\Fortie\FortieRequest;


/**
 * Base provider for the all Fortnox providers, each provider includes a
 * path (the URL extension for the provider, for example "accounts") and
 * a set of attributes (both writeable and required).
 *
 * Before a request is sent to Fortnox the supplied parameter array will
 * be sanitized according to the rules in Fortnox defined by the online
 * documentation (http://developer.fortnox.se/documentation/). When the
 * data has been verified the data is sent to the Guzzle client.
 *
 * The response (either XML or JSON) is then turned into an array and
 * retured to the caller.
 */
abstract class ProviderBase
{

  /**
   * A reference to the client in Fortie.php
   */
  protected $client = null;


  /**
   * The base path for the Provider.
   */
  protected $basePath = null;


  /**
   * List of readable attributes.
   */
  protected $attributes = [
  ];


  /**
   * The writeable attributes.
   */
  protected $writeable = [
  ];


  /**
   * The maximum requests per second per access-token.
   */
  protected $rate_limit;


  /**
   * The minimum required attributes for a create request.
   */
  protected $required_create = [
  ];


  /**
   * The minimum required attributes for an update request.
   */
  protected $required_update = [
  ];


  /**
   * The current page of the listed query.
   */
  public $page = 1;


  /**
   * The default setting for the
   * offset of listed queries.
   */
  protected $default_offset = 0;


  /**
   * The current offset of listed queries.
   */
  public $offset = 0;


  /**
   * The default setting for the
   * limit of items per page of listed queries.
   */
  protected $default_limit = 100;


  /**
   * The limit of items per page of listed queries.
   */
  public $limit = 100;


  /**
   * The time or time difference for retrieving the records since.
   * (not available for FinancialYears or Settings)
   *
   * Values can be like:
   *    2019-03-10 12:39
   *    -2 days
   *    last monday
   */
  public $timespan = null;


  /**
   * The possible values for filtering.
   */
  protected $available_filters = [];


  /**
   * The filtering parameter for retrieving query.
   */
  public $filter = null;


  /**
   * The ordering type parameter for retrieving query.
   */
  public $sort_order = null;


  /**
   * The ordering method parameter for retrieving query.
   */
  public $sort_by = 'ascending';


  /**
   * Create a new provider instance, pass the Guzzle client
   * reference.
   *
   * @return void
   */
  public function __construct(&$client)
  {
    $this->client = $client;
  }


  /**
   * Handle the response, whether it's JSON or XML.
   */
  protected function handleResponse (\GuzzleHttp\Psr7\Response $response)
  {
    $content_type = $response->getHeader('Content-Type');

    if (in_array('application/json', $content_type)) {
      return json_decode($response->getBody());
    }

    else if (in_array('application/xml', $content_type)) {
      $reader = new \Sabre\Xml\Reader();
      $reader->xml($response->getBody());
      return $reader->parse();
    }
    else if (in_array('application/pdf', $content_type)) {
	    return $response->getBody()->getContents();
    }
  }


  /**
   * This will perform filtering on the supplied data, used when uploading data
   * to Fortnox.
   */
  protected function handleData ($requiredArr, $bodyWrapper, $data)
  {
    // Filter invalid data
    $filtered = array_intersect_key($data, array_flip($this->attributes));;

    // Filter non-writeable data
    $writeable = array_intersect_key($filtered, array_flip($this->writeable));

    // Make sure all required data are set
    if (! (count(array_intersect_key(array_flip($requiredArr), $writeable)) === count($requiredArr))) {
      throw new MissingRequiredAttributeException($requiredArr);
    }

    // Wrap the body as required by Fortnox
    $body = [
      $bodyWrapper => $writeable
    ];

    return $body;
  }


  /**
   * Send a FortieRequest to Fortnox
   */
  public function send (FortieRequest $request)
  {
    $response = null;

    try {
      switch ($request->getMethod()) {
        case 'delete':
        case 'DELETE':
          $response = $this->client->delete($request->getUrl());
          break;

        case 'get':
        case 'GET':
          $response = $this->client->get($request->getUrl());
          break;
        
        case 'post':
        case 'POST':
          // If there's a file path available then we'll proceed with uploading that file
          if (!is_null($request->getFile())) {
            $response = $this->client->post(
              $request->getUrl(),
              [
                'multipart' => [
                  [
                    'name' => $request->getFile(),
                    'contents' => file_get_contents($request->getFile()),
                    'filename' => $request->getFile(),
                  ],
                ],
              ]
            );
          }

          // otherwise assume it's normal POST
          else {
            // Get the correct filter, if there is nothing required then set empty array
            $required = (!is_null($request->getRequired()) ? $request->getRequired() : []);

            $body = $this->handleData($required, $request->getWrapper(), $request->getData());
            
            $response = $this->client->post($request->getUrl(), ['json' => $body]);
          }
          break;

        case 'put':
        case 'PUT':
          // If there's a file path available then we'll proceed with uploading that file
          if (!is_null($request->getFile())) {
            $body = fopen($request->getFile(), 'r');
            $response = $this->client->put($request->getUrl(), ['body' => $body]);
          }

          // otherwise assume it's normal PUT
          else {
            // Get the correct filter, if there is nothing required then set empty array
            $required = (!is_null($request->getRequired()) ? $request->getRequired() : []);

            $body = $this->handleData($required, $request->getWrapper(), $request->getData());
            $response = $this->client->put($request->getUrl(), ['json' => $body]);
          }
      }

      return $this->handleResponse($response);
    }
    catch (\GuzzleHttp\Exception\ClientException $e) {
      $response = $e->getResponse();
      $responseBodyAsString = $response->getBody()->getContents();
      $jsonError = json_decode($responseBodyAsString);

      // Fortnox has a rate limit 4 requests per second
      // Exceeding limit will response HTTP 429 (Too Many Requests)
      // In that case just try again
      if ($response->getStatusCode() == 429) {
        $waitInMicroseconds = round( 1000000 / $this->getRateLimit() );
        usleep($waitInMicroseconds);
        return $this->send($request);
      }

      // Because Fortnox API can use both non-capitalized and capitalized parameters.
      if (property_exists($jsonError, 'ErrorInformation')) {
        if (property_exists($jsonError->ErrorInformation, 'error')) {
          throw new FortnoxException(
            $jsonError->ErrorInformation->error,
            $jsonError->ErrorInformation->message,
            $jsonError->ErrorInformation->code,
            $e
          );
        } else {
          throw new FortnoxException(
            $jsonError->ErrorInformation->Error,
            $jsonError->ErrorInformation->Message,
            $jsonError->ErrorInformation->Code,
            $e
          );
        }
      }
      else {
        throw new FortnoxException(
          $jsonError->message,
          $jsonError->message,
          null,
          $e
      );
      }
    }
  }


  /**
   * Returns the rate limit.
   *
   * @return int
   */
  public function getRateLimit()
  {
    if (empty($this->rate_limit)) {
      $this->rate_limit = config('fortie.default.rate_limit',
          config('fortie::default.rate_limit',
          4
        )
      );
    }

    return $this->rate_limit;
  }


  /**
   * Sets the current page number for the query request.
   */
  public function page($page)
  {
    $this->page = $page;

    return $this;
  }


  /**
   * Sets the item offset for the query request.
   */
  public function offset($offset)
  {
    $this->offset = $offset;

    return $this;
  }


  /**
   * Sets the item listing limit for the query request.
   */
  public function limit($limit)
  {
    $this->limit = $limit;

    return $this;
  }


  /**
   * Sets the listing limit to unlimited
   * for the query request.
   */
  public function unlimited()
  {
    $this->limit = -1;

    return $this;
  }


  /**
   * Sets the time limit for the last modification time
   * of the items to be listed.
   */
  public function timespan($timespan)
  {
    $this->timespan = $timespan;

    return $this;
  }


  /**
   * Sets the filtering parameter for the query request.
   */
  public function filter($filter)
  {
    if (in_array($filter, $this->available_filters)) {
      $this->filter = $filter;
    }

    return $this;
  }


  /**
   * Sets the ordering type parameter for the query request.
   */
  public function sortBy($sort_by)
  {
    if (in_array($sort_by, $this->attributes)) {
      $this->sort_by = strtolower($sort_by);
    }

    return $this;
  }


  /**
   * Sets the ordering method parameter for the query request.
   */
  public function sortOrder($sort_order)
  {
    switch ($sort_order) {
      case '0':
      case 'DESC':
      case 'descending':
        $this->sort_order = 'descending';
        break;

      default:
      case '1':
      case 'ASC':
      case 'ascending':
        $this->sort_order = 'ascending';
        break;
      }

      return $this;
  }
}
