<?php namespace Wetcat\Fortie\PriceLists;

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

use Wetcat\Fortie\ProviderBase;


class Provider extends ProviderBase {


  protected $attributes = [
    'Url',
    'Code',
    'Description',
    'Comments',
    'PreSelected',
  ];

  protected $writeable = [
    'Code',
    'Description',
    'Comments',
  ];

  protected $required = [
    'Code',
    'Description',
  ];

  /**
   * Override the REST path
   */
  protected $path = 'pricelists';


  /**
   * Retrieves a list of price lists.
   *
   * @return array
   */
  public function all ()
  {
    return $this->sendRequest('GET');
  }


  /**
   * Retrieves a single price list.
   *
   * @param $id
   * @return array
   */
  public function find ($id)
  {
    return $this->sendRequest('GET', $id);
  }


  /**
   * Creates a price list.
   *
   * @param array   $params
   * @return array
   */
  public function create (array $params)
  {
    return $this->sendRequest('POST', null, 'PriceList', $params);
  }


  /**
   * Updates a price list.
   *
   * @param array   $params
   * @return array
   */
  public function update ($id, array $params)
  {
    return $this->sendRequest('PUT', $id, 'PriceList', $params);
  }

}