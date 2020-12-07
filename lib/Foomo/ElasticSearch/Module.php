<?php

/*
 * This file is part of the foomo Opensource Framework.
 *
 * The foomo Opensource Framework is free software: you can redistribute it
 * and/or modify it under the terms of the GNU Lesser General Public License as
 * published  by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * The foomo Opensource Framework is distributed in the hope that it will
 * be useful, but WITHOUT ANY WARRANTY; without even the implied warranty
 * of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License along with
 * the foomo Opensource Framework. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Foomo\ElasticSearch;

/**
 * @link www.foomo.org
 * @license www.gnu.org/licenses/lgpl.txt
 * @author bostjanm
 */
class Module extends \Foomo\Modules\ModuleBase
{
	//---------------------------------------------------------------------------------------------
	// ~ Constants
	//---------------------------------------------------------------------------------------------

	/**
	 * the name of this module
	 *
	 */
	const NAME = 'Foomo.ElasticSearch';
	const VERSION = '7.10.0'; //version and submodule tag matched es version

	//---------------------------------------------------------------------------------------------
	// ~ Overriden static methods
	//---------------------------------------------------------------------------------------------

	/**
	 * Your module needs to be set up, before being used - this is the place to do it
	 */
	public static function initializeModule()
	{
	}

	/**
	 * Get a plain text description of what this module does
	 *
	 * @return string
	 */
	public static function getDescription()
	{
		return 'Elastic search integration using the Elastica PHP client';
	}

	/**
	 * get all the module resources
	 *
	 * @return \Foomo\Modules\Resource[]
	 */
	public static function getResources()
	{
		return array(
			\Foomo\Modules\Resource\ComposerPackage::getResource('elasticsearch/elasticsearch', '7.10.0'),
			\Foomo\Modules\Resource\ComposerPackage::getResource('ruflin/elastica', '7.0.0') //Elastica client
		);
	}

	/**
	 * get module config
	 * @return \Foomo\ElasticSearch\DomainConfig
	 */
	public static function getModuleConfig() {
		return self::getConfig('Foomo.ElasticSearch.config');
	}
}