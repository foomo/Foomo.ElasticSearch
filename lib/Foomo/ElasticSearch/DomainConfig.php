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

use Foomo\Config\AbstractConfig;

class DomainConfig extends AbstractConfig
{
	const NAME = 'Foomo.ElasticSearch.config';
	/**
	 * es host
	 * @var string
	 */
	public $host = '127.0.0.1';
	/**
	 * es port
	 * @var string
	 */
	public $port = 9200;
	/**
	 * es shards
	 * @var int
	 */
	public $numberOfShards = 1;
	/**
	 * es replicas
	 * @var int
	 */
	public $numberOfReplicas = 1;
	/**
	 * the name used for type
	 * @var string
	 */
	public $dataType = 'product';
	/**
	 * the prefix used for the index name (2 indices + alias name)
	 * @var string
	 */
	public $indexName = 'products';
	/**
	 * min score for result
	 * @var float
	 */
	public $minScore = 0.6;
	/**
	 * max results returned by search
	 * @var int
	 */
	public $maxResults = 100;
	/**
	 * default index analyzer
	 * @var string
	 */
	public $defaultIndexAnalyzer = 'german_index_analyzer';
	/**
	 * default search analyzer
	 * @var string
	 */
	public $defaultSearchAnalyzer = 'search_analyzer';

	public $analysis = [
		'analyzer' => [

			'search_analyzer' => [
				'type' => 'custom',
				'tokenizer' => 'standard',
				'filter' => [
					'german_synonyms',
					'standard',
					'lowercase',
					'english_stemmer'
				]
			],

			'german_index_analyzer' => [
				'type' => 'custom',
				'tokenizer' => 'icu_tokenizer',
				'filter' => [
					'german_synonyms',
					'german_keywords',
					'word_delimiter',
					'german_stemmer',
					'icu_folding',
					'german_stop'
				]
			],

			'english_index_analyzer' => [
				'type' => 'custom',
				'tokenizer' => 'standard',
				'filter' => [
					'lowercase',
					'english_stemmer',
					'english_keywords',
					'english_stop'
				]
			],

			'french_index_analyzer' => [
				'type' => 'custom',
				'tokenizer' => 'standard',
				'filter' => [
					"french_elision",
					"lowercase",
					"french_stop",
					"french_keywords",
					"french_stemmer"
				]
			],
		],

		'filter' => [
			'german_stemmer' => [
				'type' => 'snowball',
				'language' => 'German2',
			],

			'english_stemmer' => array(
				'type' => 'snowball',
				'language' => 'English'
			),

			'french_stemmer' => [
				'type' => 'stemmer',
				'language' => 'light_french'
			],

			'german_stop' => [
				'type' => 'stop',
				'stopwords' => '_german_'
			],

			'english_stop' => [
				'type' => 'stop',
				'stopwords' => '_english_'
			],

			'french_stop' => [
				'type' => 'stop',
				'stopwords' => '_french_'
			],

			'french_elision' => [
				'type' => 'elision',
				'articles' => ["l", "m", "t", "qu", "n", "s", "j", "d", "c", "jusqu", "quoiqu", "lorsqu", "puisqu"]
			],

			'german_keywords' => [
				'type' => 'keyword_marker',
				'keywords' => ['keyword']
			],

			'english_keywords' => [
				'type' => 'keyword_marker',
				'keywords' => ['keyword']
			],

			'french_keywords' => [
				'type' => 'keyword_marker',
				'keywords' => ['keyword']
			],

			'german_synonyms' => [
				"expand" => 1,
				'type' => 'synonym',

				'synonyms' => [
					'red, rot',
					'black, schwarz',
					'green, grün',
					'yellow, gelb',
					'white, weiß, weiss',
					'magenta, magenta',
					'cyan, cyan',
					'taupe, taupe',
					'brown, braun',
					'blue, blau',
					'light, licht, lt',
					'dark, dunkel, dk',
					'orange, orange',
					'pink, pink',
					'violet, violett, viola',
					'gray, grau',
					'mint, minze',
					'beige, beige',
					'lila, lila',
					'mint, green, grün'
				],
			],

			'english_synonyms' => [
				"expand" => 1,
				'type' => 'synonym',
				'synonyms' => ['test, test'],
			],

			'french_synonyms' => [
				"expand" => 1,
				'type' => 'synonym',
				'synonyms' => ['test, test'],
			],

			"trigrams_filter" => [
				"type" => "ngram",
				"min_gram" => 3,
				"max_gram" => 3
			],

			'german_decompound' => [
				'type' => 'dictionary_decompounder',
				'word_list_path' => 'german-common-nouns.txt'
			],

			'english_decompound' => [
				'type' => 'dictionary_decompounder',
				'word_list_path' => 'english-common-nouns.txt'
			],

		]
	];
	/**
	 * mandatory fields - must be provided in the import data
	 * and must not be empty
	 *
	 * @var string
	 */
	public $mandatoryFields = ['id'];
	/**
	 * @var array field_name => [elastic search type .. specify filters etc]
	 */
	public $fields = array(

		'id' => [
			'type' => 'string',
			'include_in_all' => TRUE,
			'analyzer' => 'english_index_analyzer',
			'index' => 'not_analyzed'
		],

		'sizeId' => [
			'type' => 'string',
			'include_in_all' => TRUE,
			'analyzer' => 'english_index_analyzer',
			'index' => 'not_analyzed'
		],

		'brand' => [
			'type' => 'string',
			'include_in_all' => TRUE,
			'analyzer' => 'english_index_analyzer'
		],

		'gender' => [
			'type' => 'string',
			'include_in_all' => TRUE,
			'analyzer' => 'english_index_analyzer',
			'index' => 'not_analyzed'
		],

		'name_de' => [
			'type' => 'string',
			'include_in_all' => TRUE,
			'analyzer' => 'german_index_analyzer'
		],

		'description_de' => [
			'type' => 'string',
			'include_in_all' => TRUE,
			'analyzer' => 'german_index_analyzer'
		],

		'name_en' => [
			'type' => 'string',
			'include_in_all' => false,
			'analyzer' => 'english_index_analyzer'
		],

		'description_en' => [
			'type' => 'string',
			'include_in_all' => false,
			'analyzer' => 'english_index_analyzer'
		],

		'name_fr' => [
			'type' => 'string',
			'include_in_all' => false,
			'analyzer' => 'french_index_analyzer'
		],

		'description_fr' => [
			'type' => 'string',
			'include_in_all' => false,
			'analyzer' => 'french_index_analyzer'
		],

		'color_de' => [
			'type' => 'string',
			'include_in_all' => TRUE,
			'analyzer' => 'german_index_analyzer'
		],

		'color_en' => [
			'type' => 'string',
			'include_in_all' => TRUE,
			'analyzer' => 'english_index_analyzer'
		],

		'color_fr' => [
			'type' => 'string',
			'include_in_all' => TRUE,
			'analyzer' => 'french_index_analyzer'
		],

		'categories_de' => [
			'type' => 'string',
			'include_in_all' => TRUE,
			'analyzer' => 'german_index_analyzer'
		],

		'categories_en' => [
			'type' => 'string',
			'include_in_all' => TRUE,
			'analyzer' => 'english_index_analyzer'
		],

		'categories_fr' => [
			'type' => 'string',
			'include_in_all' => TRUE,
			'analyzer' => 'french_index_analyzer'
		],

		//******************************************************************************
		// suggest fields
		//******************************************************************************

		'suggest_name_de' => [
			'type' => 'completion',
			"index_analyzer" => "simple",
			"search_analyzer" => "simple",
			"payloads" => true,
			'context' => [
				'gender' =>
					[
						'type' => 'category',
						'path' => "gender",
						'default' => ['female'],
					]
			],
		],

		'suggest_name_en' => [
			'type' => 'completion',
			"index_analyzer" => "simple",
			"search_analyzer" => "simple",
			"payloads" => true,
			'context' => [
				'gender' =>
					[
						'type' => 'category',
						'path' => "gender",
						'default' => ['female'],
					]
			],
		],

		'suggest_name_fr' => [
			'type' => 'completion',
			"index_analyzer" => "simple",
			"search_analyzer" => "simple",
			"payloads" => true,
			'context' => [
				'gender' =>
					[
						'type' => 'category',
						'path' => "gender",
						'default' => ['female'],
					]
			],
		],

		'suggest_categories_de' => [
			'type' => 'completion',
			'index_analyzer' => 'simple',
			'search_analyzer' => 'simple',
			'payloads' => true,
			'context' => [
				'gender' =>
					[
						'type' => 'category',
						'path' => 'gender',
						'default' => ['female'],
					]
			],
		],

		'suggest_categories_en' => [
			'type' => 'completion',
			'index_analyzer' => 'simple',
			'search_analyzer' => 'simple',
			'payloads' => true,
			'context' => [
				'gender' =>
					[
						'type' => 'category',
						'path' => "gender",
						'default' => ['female'],
					]
			],
		],

		'suggest_categories_fr' => [
			'type' => 'completion',
			'index_analyzer' => 'simple',
			'search_analyzer' => 'simple',
			'payloads' => true,
			'context' => [
				'gender' =>
					[
						'type' => 'category',
						'path' => 'gender',
						'default' => ['female'],
					]
			],
		],

		'suggest_color_de' => [
			'type' => 'completion',
			'index_analyzer' => 'simple',
			'search_analyzer' => 'simple',
			'payloads' => true,
			'context' => [
				'gender' =>
					[
						'type' => 'category',
						'path' => 'gender',
						'default' => ['female'],
					]
			],
		],

		'suggest_color_en' => [
			'type' => 'completion',
			'index_analyzer' => 'simple',
			'search_analyzer' => 'simple',
			'payloads' => true,
			'context' => [
				'gender' =>
					[
						'type' => 'category',
						'path' => "gender",
						'default' => ['female'],
					]
			],
		],

		'suggest_color_fr' => [
			'type' => 'completion',
			'index_analyzer' => 'simple',
			'search_analyzer' => 'simple',
			'payloads' => true,
			'context' => [
				'gender' =>
					[
						'type' => 'category',
						'path' => 'gender',
						'default' => ['female'],
					]
			],
		],

		'suggest_de' => ['type' => "completion",
			"index_analyzer" => "simple",
			"search_analyzer" => "simple",
			"payloads" => true,
			'context' => [
				'gender' =>
					[
						'type' => 'category',
						'path' => "gender",
						'default' => ['female'],
					]
			],
		],

		'suggest_en' => ['type' => "completion",
			"index_analyzer" => "simple",
			"search_analyzer" => "simple",
			"payloads" => true,
			'context' => [
				'gender' =>
					[
						'type' => 'category',
						'path' => "gender",
						'default' => ['female'],
					]
			],
		],

		'suggest_fr' => ['type' => "completion",
			"index_analyzer" => "simple",
			"search_analyzer" => "simple",
			"payloads" => true,
			'context' => [
				'gender' =>
					[
						'type' => 'category',
						'path' => "gender",
						'default' => ['female'],
					]
			],
		],

		'suggest_id' => [
			'type' => 'completion',
			'index_analyzer' => 'whitespace',
			'search_analyzer' => 'whitespace',
			'payloads' => true
		],

		'_boost' => [
			'type' => 'float',
			'include_in_all' => false
		],

		"_all" => [
			'enabled' => true
		]

	);
}