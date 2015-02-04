<?php

namespace Foomo\ElasticSearch\Interfaces;

interface Search {
	/**
	 * find documents
	 * @param string $query
	 * @param string $gender
	 * @param string $language
	 * @return array indexed document as array
	 */
	public static function findDocuments($query, $gender, $language = 'de');

	/**
	 * get suggestions
	 * @param string $term
	 * @param string $gender
	 *
	 * @return string[]
	 */
	public static function getSuggestions($term, $gender, $language);

}