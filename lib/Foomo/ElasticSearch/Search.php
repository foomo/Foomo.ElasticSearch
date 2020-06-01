<?php

namespace Foomo\ElasticSearch;

use Elastica\Util;

class Search implements \Foomo\ElasticSearch\Interfaces\Search
{
	/**
	 * @var \Foomo\ElasticSearch\DomainConfig
	 */
	public static $config;

	public static function init(\Foomo\ElasticSearch\DomainConfig $config)
	{
		self::$config = $config;
	}

	/**
	 * find documents
	 * @param string $query
	 * @param string $gender
	 * @param string $language
	 * @return array indexed document as array
	 */
	public static function findDocuments($query, $gender, $language = 'de')
	{
		$ret = array();
		$elasticaResults = static::find($query, $gender, $language);
		foreach ($elasticaResults as $elasticaResult) {
			$doc = $elasticaResult->getData();
			$ret[] = $doc;
		}
		return $ret;
	}

	/**
	 * @param string $query
	 * @param string $gender
	 * @param string $language
	 * @return \Elastica\Result[]
	 */
	protected static function find($query, $gender, $language)
	{
		$elasticaClient = Index::getClient(self::$config);

		$aliasName = self::$config->indexName . '-index';
		$escapedQuery = Util::replaceBooleanWordsAndEscapeTerm($query);
		$elasticaIndex = $elasticaClient->getIndex($aliasName);

		$boolQuery = new \Elastica\Query\Bool();

		// Define a Query. We want a string query.
		$elasticaQueryString = new \Elastica\Query\QueryString();
		$elasticaQueryString->setDefaultOperator('OR');
		$elasticaQueryString->setQuery($escapedQuery);
		$elasticaQueryString->setBoost(1);

		$elasticaQueryString->setDefaultField('suggest');
		$elasticaQueryString->setFields(array('name_' . $language, 'suggest', 'categories_' . $language, 'color_' . $language));


		$colorQuery = new \Elastica\Query\QueryString();
		$colorQuery->setQuery($escapedQuery);
		$colorQuery->setDefaultField('color_' . $language);
		$colorQuery->setBoost(1);

		$nameQuery = new \Elastica\Query\QueryString();
		$nameQuery->setQuery($escapedQuery);
		$nameQuery->setDefaultField('name_' . $language);
		$nameQuery->setBoost(1);

		$categoriesQuery = new \Elastica\Query\QueryString();
		$categoriesQuery->setQuery($escapedQuery);
		$categoriesQuery->setDefaultField('categories_' . $language);
		$categoriesQuery->setBoost(1);

		$idQuery = new \Elastica\Query\Prefix();
		$idQuery->setPrefix('id', strtolower($query));

		if (self::isId($query)) {
			$boolQuery->addMust($idQuery);
		} else {
			$boolQuery->addShould($idQuery);
			$boolQuery->addShould($colorQuery);
			$boolQuery->addShould($nameQuery);
			$boolQuery->addShould($elasticaQueryString);
			$boolQuery->addShould($categoriesQuery);

		}

		// Create the actual search object with some data.
		$elasticaQuery = new \Elastica\Query();
		$elasticaQuery->setQuery($boolQuery);
		$elasticaQuery->setSize(self::$config->maxResults);
		//var_dump($elasticaQuery->toArray());

		//Search on the index.
		$elasticaQuery->setMinScore(floatval(self::$config->minScore));
		$elasticaResultSet = $elasticaIndex->search($elasticaQuery);
		$elasticaResults = $elasticaResultSet->getResults();

		return $elasticaResults;
	}


	public static function isId($query)
	{
		$numbers = str_replace('-', '', $query);
		if (is_numeric($numbers)) {
			return true;
		} else {
			return false;
		}
	}


	/**
	 * get suggestions
	 * @param string $term
	 * @param string $gender
	 *
	 * @return string[]
	 */
	public static function getSuggestions($term, $gender, $language = 'de')
	{

		$elasticaClient = Index::getClient(self::$config);

		$aliasName = self::$config->indexName . '-index';
		$elasticaIndex = $elasticaClient->getIndex($aliasName);

		$query = array(

			"suggest_name_" . $language => array(
				"text" => $term,
				"completion" => array(
					"field" => "suggest_" . $language,
					'context' => array('gender' => $gender)
				)
			),

			"suggest_categories_" . $language => array(
				"text" => $term,
				"completion" => array(
					"field" => "suggest_categories_" . $language,
					'context' => array('gender' => $gender)
				)
			),

			"suggest_color_" . $language => array(
				"text" => $term,
				"completion" => array(
					"field" => "suggest_color_" . $language,
					'context' => array('gender' => $gender)
				)
			),

			"suggest_id" => array(
				"text" => $term,
				"completion" => array(
					"field" => "suggest_id"
				)
			),

		);

		$path = $elasticaIndex->getName() . '/_suggest';
		$response = $elasticaClient->request($path, \Elastica\Request::GET, $query);
		$responseArray = $response->getData();

		$ret = array();

		foreach (array("suggest_" . $language, "suggest_name_" . $language, "suggest_color_" . $language, "suggest_categories_" . $language, 'suggest_id') as $key) {
			if (isset($responseArray[$key])) {
				foreach ($responseArray[$key] as $val) {

					foreach ($val['options'] as $part) {
						foreach ($part as $key1 => $arr) {
							if ($key1 == 'text') {
								$ret[] = $arr;
							}
						}
					}
				}
			}
		}

		//if no auto complete, use term search
		/*if (count($ret) == 0) {
			$suggest = new Suggest();

			$suggest1 = new Term('suggest1', 'name_de');
			$suggest1->setMinWordLength(2);
			$suggest->addSuggestion($suggest1->setText($term));

			$suggest2 = new Term('suggest2', 'id');
			$suggest2->setMinWordLength(2);
			$suggest->addSuggestion($suggest2->setText($term));

			$result = $elasticaIndex->search($suggest);

			$responseArray = $result->getSuggests();

			foreach (array('suggest1', 'suggest2') as $key) {
				if (isset($responseArray[$key])) {
					foreach ($responseArray[$key] as $val) {
						foreach ($val['options'] as $part) {
							foreach ($part as $key1 => $arr) {
								if ($key1 == 'text') {
									$ret[] = $arr;
								}
							}
						}
					}
				}
			}

		}
		*/
		if (self::isId($term)) {
			return $ret;
		} else {
			return $ret;
		}

	}


}