<?php

namespace Foomo\ElasticSearch;

use Elastica\Query;
use Elastica\Util;
use Kennys\Search\Index;

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
	public static function getSuggestions($term, $gender = 'female', $language = 'de')
	{

		$elasticaClient = \OuiSet\Search\Index::getClient(self::$config);
		$aliasName = self::$config->indexName . '-index';
		$elasticaIndex = $elasticaClient->getIndex($aliasName);
		$options = [];

		if (self::isId($term)) {
			$suggestFrom = ['suggest_id'];
		} else {
			$suggestFrom = ['suggest_categories_de', 'suggest_name_de', 'suggest_de', 'suggest_id', 'suggest_color_de'];
		}

		foreach ($suggestFrom as $suggestName) {
			$suggest = new \Elastica\Suggest\Completion($suggestName, $suggestName);
			if (!self::isId($term)) {
				$suggest->setFuzzy(['fuzziness' => 1]);

			}
			$suggest->setPrefix($term);

			$suggest->setSize(100);

			$resultSet = $elasticaIndex->search(Query::create($suggest));

			if ($resultSet->hasSuggests()) {
				$suggests = $resultSet->getSuggests();
				foreach ($suggests[$suggestName][0]['options'] as $option) {
					$options[] = $option['text'];
				}

			}
		}
		$candidates = array_values(array_unique($options));

		if (!self::isId($term)) {
			return $candidates;
		} else {
			foreach ($candidates as $candidate) {
				if (self::startsWith($candidate, $term)) {
					$ret[] = $candidate;
				}
			}
			return $ret;
		}

	}

	protected static function startsWith($haystack, $needle)
	{
		return $needle === "" || strpos($haystack, $needle) === 0;
	}


}