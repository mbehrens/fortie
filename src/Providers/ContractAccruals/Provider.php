<?php namespace Wetcat\Fortie\Providers\ContractAccruals;

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

use Wetcat\Fortie\Providers\ProviderBase;
use Wetcat\Fortie\FortieRequest;

class Provider extends ProviderBase {

  protected $attributes = [
    'Url',
    'AccrualAccount',
    'CostAccount',
    'Description',
    'AccrualRows',
    'DocumentNumber',
    'Period',
    'Times',
    'Total',
    'VATIncluded',
  ];


  protected $writeable = [
    // 'Url',
    'AccrualAccount',
    'CostAccount',
    'Description',
    'AccrualRows',
    'DocumentNumber',
    // 'Period',
    // 'Times',
    'Total',
    'VATIncluded',
  ];


  protected $required_create = [
  ];


  protected $required_update = [
  ];


  /**
   * Override the REST path
   */
  protected $basePath = 'contractaccruals';


  /**
   * Retrieves a list of contract accruals.
   *
   * @return array
   */
  public function all ()
  {
    $req = new FortieRequest();
    $req->method('GET');
    $req->path($this->basePath);

    return $this->send($req->build());
  }


  /**
   * Retrieves a single contract accrual.
   *
   * @param $documentNumber
   * @return array
   */
  public function find ($documentNumber)
  {
    $req = new FortieRequest();
    $req->method('GET');
    $req->path($this->basePath)->path($documentNumber);

    return $this->send($req->build());
  }


  /**
   * Creates a contract accrual.
   *
   * @param array   $data
   * @return array
   */
  public function create (array $data)
  {
    $req = new FortieRequest();
    $req->method('POST');
    $req->path($this->basePath);
    $req->wrapper('ContractAccrual');
    $req->data($data);
    $req->setRequired($this->required_create);

    return $this->send($req->build());
  }


  /**
   * Updates an attendance transaction.
   *
   * @param $documentNumber
   * @param array   $data
   * @return array
   */
  public function update ($documentNumber, array $data)
  {
    $req = new FortieRequest();
    $req->method('PUT');
    $req->path($this->basePath)->path($documentNumber);
    $req->wrapper('ContractAccrual');
    $req->setRequired($this->required_update);
    $req->data($data);

    return $this->send($req->build());
  }


  /**
   * Deletes the article permanently.
   *
   * You need to supply the unique article number that was returned when the 
   * article was created or retrieved from the list of articles.
   *
   * @param $documentNumber
   * @return null
   */
  public function delete ($documentNumber)
  {
    $req = new FortieRequest();
    $req->method('DELETE');
    $req->path($this->basePath)->path($documentNumber);

    return $this->send($req->build());
  }

}
