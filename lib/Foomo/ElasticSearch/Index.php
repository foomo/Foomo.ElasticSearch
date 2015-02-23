<?php
/*
 * This file is part of the foomo Opensource Framework.
 * 
 * The foomo Opensource Framework is free software: you can redistribute it
 * and/or modify it under the terms of the GNU Lesser General Public License as
 * published  by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 * 
 * The foomo Opensource Framework is distributed in the hope that it will
 * be useful, but WITHOUT ANY WARRANTY; without even the implied warranty
 * of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License along with
 * the foomo Opensource Framework. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Foomo\ElasticSearch;


class Index extends \Foomo\ElasticSearch\Interfaces\Index {
	/**
	 * @var \Elastica\Client
	 */
	protected $client = null;
	/**
	 * @var \Elastica\Index
	 */
	protected $index = null;
	/**
	 * @var \Elastica\Index
	 */
	protected $tempIndex = null;
	/**
	 * @var string
	 */
	protected $indexName1;
	/**
	 * @var string
	 */
	protected $indexName2;
	/**
	 * @var string
	 */
	protected $aliasName;
	/**
	 * @var string
	 */
	protected $selectedIndexName;
	
	protected $config;
	/**
	 * initialize indices. this must be run before the import
	 * this reference implementation uses two indices and switches between them
	 * using an alias name
	 * @param \Foomo\ElasticSearch\DomainConfig $config
	 */
	public function init(\Foomo\ElasticSearch\DomainConfig $config)
	{
			$this->config = self::addExternalSynonyms($config);
			$this->config = self::addExternalCommonWords($this->config);

			$this->indexName1 = $config->indexName . '-1';
			$this->indexName2 = $config->indexName . '-2';
			$this->aliasName = $config->indexName . '-index';

			$this->client = self::getClient($this->config);

			// load indices
			$elasticaIndex1 = $this->client->getIndex($this->indexName1);
			$elasticaIndex2 = $this->client->getIndex($this->indexName2);

			if ($elasticaIndex1 && $elasticaIndex1->exists()) {
				$this->index = $elasticaIndex1;
				$this->deleteSearchIndex($this->indexName2);
				$this->tempIndex = $this->createIndexAndMapping($this->indexName2);
				$this->selectedIndexName = $this->indexName1;
			} else if ($elasticaIndex2 && $elasticaIndex2->exists()) {
				$this->index = $elasticaIndex2;
				$this->deleteSearchIndex($this->indexName1);
				$this->tempIndex = $this->createIndexAndMapping($this->indexName1);
				$this->selectedIndexName = $this->indexName2;
			} else {
				//default is first index but create it first
				$this->index = $this->createIndexAndMapping($this->indexName1);
				$this->deleteSearchIndex($this->indexName2);
				$this->$tempIndex = $this->createIndexAndMapping($this->indexName2);
				$this->selectedIndexName = $this->indexName1;
			}
			$this->index->addAlias($this->aliasName, true);
	}

	/**
	 * @param array $data
	 * @return mixed
	 * @throws \Foomo\ElasticSearch\Exception
	 */
	public function insertDocument($data) {
		//throw an exception if data not ok
		$this->validateData($data);
		// Add document to type
		$type = $this->tempIndex->getType($this->config->dataType);
		$document = new \Elastica\Document($data['id'], $data);
		return $type->addDocument($document);
	}

	/**
	 * run after import done
	 * the reference implementation swaps the indices
	 */
	public function commit() {
		$this->swapIndices();
	}


	/**
	 * swap indices after import
	 * uses alias assignment for swapping
	 */
	protected function swapIndices()
	{
		if ($this->tempIndex && $this->tempIndex->exists()) {
			$this->tempIndex->addAlias($this->aliasName, true);
			$this->deleteSearchIndex($this->selectedIndexName);
			$this->tempIndex->optimize();
			$this->tempIndex->refresh();
		}
	}

	/**
	 * @param string $indexName
	 * @return \Elastica\Index
	 */
	protected function createIndexAndMapping($indexName)
	{
		$index = self::getClient($this->config)->getIndex($indexName);
		//create the index if not there
		if (!$index || !$index->exists()) {
			$index = $this->createIndex($indexName);
		}
		$this->createMapping($index);
		return $index;
	}

	/**
	 * create index
	 * @param string $indexName
	 * @return \Elastica\Index
	 */
	protected function createIndex($indexName)
	{
		$index = self::getClient($this->config)->getIndex($indexName);
		$data = [
			'number_of_shards' => $this->config->numberOfShards,
			'number_of_replicas' => $this->config->numberOfReplicas,
			'analysis' => $this->config->analysis
		];
		$index->create($data, $deleteIndex = true);
		return $index;
	}

	/**
	 * create mapping
	 * @param \Elastica\Index $index
	 */
	protected function createMapping($index) {
		//Create a type
		$elasticaType = $index->getType($this->config->dataType);

		// Define mapping
		$mapping = new \Elastica\Type\Mapping();
		$mapping->setType($elasticaType);
		$mapping->setParam('index_analyzer', $this->config->defaultIndexAnalyzer);
		$mapping->setParam('search_analyzer', $this->config->defaultSearchAnalyzer);

		// Set mapping
		$mapping->setProperties($this->config->fields);

		// Send mapping to type
		$mapping->send();
	}

	/**
	 * delete index
	 * @param string $indexName
	 */
	protected function deleteSearchIndex($indexName)
	{
		$index = self::getClient($this->config)->getIndex($indexName);
		if ($index->exists()) {
			$index->delete();
		}
	}

	protected function validateData($data) {
		$dataFields = array_keys($data);
		$configuredFields = array_keys($this->config->fields);
		$invalidFields = [];
		foreach ($dataFields as $dataField) {
			if (!in_array($dataField, $configuredFields)) {
				$invalidFields[] = $dataField;
			}
		}
		if (count($invalidFields) > 0) {
			throw new Exception('invalid fields supplied ' . implode(', ', $invalidFields));
		}

		$missingMandatoryFields = [];
		foreach ($this->config->mandatoryFields as $dataField) {
			if (!in_array($dataField, $dataFields) || empty($data[$dataField])) {
				$missingMandatoryFields[] = $dataField;
			}
		}
		if (count($missingMandatoryFields) > 0) {
			throw new Exception('missing mandatory fields or empty value' . implode(', ', $missingMandatoryFields));
		}
	}

	/**
	 * get client
	 * @param DomainConfig $config
	 * @return \Elastica\Client
	 */
	public static function getClient(\Foomo\ElasticSearch\DomainConfig $config) {
		$client = new \Elastica\Client(
			array(
				'host' => $config->host,
				'port' => $config->port
			)
		);
		return $client;
	}


	/**
	 * @return string
	 */
	public static function getGermanCommonWordsFile() {
		return \Foomo\Config::getModuleDir(\Foomo\ElasticSearch\Module::NAME) . DIRECTORY_SEPARATOR . 'elasticsearch-resources' . DIRECTORY_SEPARATOR . 'german-common-nouns.txt';
	}

	/**
	 * @return string
	 */
	public static function getEnglishCommonWordsFile() {
		return \Foomo\Config::getModuleDir(\Foomo\ElasticSearch\Module::NAME) . DIRECTORY_SEPARATOR . 'elasticsearch-resources' . DIRECTORY_SEPARATOR . 'english-common-nouns.txt';
	}

	/**
	 * get synonyms from file
	 * @return string
	 */
	public static function getSynonyms() {
		return file_get_contents(static::getSynonymsFile());
	}

	/**
	 * store synonyms from file
	 * @param string $synonyms
	 * @return string
	 */
	public static function updateSynonyms($synonyms) {
		return file_put_contents(static::getSynonymsFile(), $synonyms);
	}

	/**
	 * @return string
	 */
	protected static function getSynonymsFile() {
		return \Foomo\ElasticSearch\Module::getVarDir() .  DIRECTORY_SEPARATOR . 'synonyms.txt';
	}



	/**
	 * @param \Foomo\ElasticSearch\DomainConfig $config
	 * @return \Foomo\ElasticSearch\DomainConfig
	 */
	protected static function addExternalSynonyms(\Foomo\ElasticSearch\DomainConfig $config) {
		$synonyms = self::getSynonyms();
		if (isset($config->analysis['filter']['german_synonyms']['synonyms'])) {
				$config->analysis['filter']['german_synonyms']['synonyms'] = array_merge(
					$config->analysis['filter']['german_synonyms']['synonyms'],
					explode(PHP_EOL, $synonyms)
			);

		} else {
			$config->analysis['filter']['german_synonyms']['synonyms'] = explode(PHP_EOL, $synonyms);
		}

		if (isset($config->analysis['filter']['english_synonyms']['synonyms'])) {
			$config->analysis['filter']['english_synonyms']['synonyms'] = array_merge(
				$config->analysis['filter']['english_synonyms']['synonyms'],
				explode(PHP_EOL, $synonyms)
			);

		} else {
			$config->analysis['filter']['english_synonyms']['synonyms'] = explode(PHP_EOL, $synonyms);
		}
		return $config;
	}

	/**
	 * add common words file link to filter if exists
	 * @param DomainConfig $config
	 * @return DomainConfig
	 */
	protected function addExternalCommonWords($config) {
		if (isset($config->analysis['filter']['german_decompound'])) {
			$config->analysis['filter']['german_decompound']['word_list_path'] = self::getGermanCommonWordsFile();
		}
		if (isset($config->analysis['filter']['english_decompound'])) {
			$config->analysis['filter']['english_decompound']['word_list_path'] = self::getEnglishCommonWordsFile();
		}
		return $config;
	}
}