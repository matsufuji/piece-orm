<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP version 5
 *
 * Copyright (c) 2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>,
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package    Piece_ORM
 * @copyright  2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    SVN: $Id$
 * @since      File available since Release 0.1.0
 */

namespace Piece::ORM::Config;

use Piece::ORM::Config;
use Piece::ORM::Exception;
use Piece::ORM::Env;
use Piece::ORM::Context::ContextRegistry;
use Piece::ORM::Exception::PEARException;

require_once 'spyc.php5';

// {{{ Piece::ORM::Config::ConfigReader

/**
 * A configuration reader for the Piece_ORM configuration DSL.
 *
 * @package    Piece_ORM
 * @copyright  2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 0.1.0
 */
class ConfigReader
{

    // {{{ properties

    /**#@+
     * @access public
     */

    /**#@-*/

    /**#@+
     * @access protected
     */

    /**#@-*/

    /**#@+
     * @access private
     */

    private $_configDirectory;

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ __construct()

    /**
     * Sets a directory where a configuration file exists to a property.
     *
     * @param string $configDirectory
     */
    public function __construct($configDirectory = null)
    {
        $this->_configDirectory = $configDirectory;
    }

    // }}}
    // {{{ read()

    /**
     * Reads configuration from the given configuration file and creates
     * a Piece::ORM::Config object.
     *
     * @return Piece::ORM::Config
     * @throws Piece::ORM::Exception
     */
    public function read()
    {
        if (is_null($this->_configDirectory)) {
            return new Config();
        }

        if (!file_exists($this->_configDirectory)) {
            throw new Exception("The configuration directory [ {$this->_configDirectory} ] is not found.");
        }

        $configFile = "{$this->_configDirectory}/piece-orm-config.yaml";
        if (!file_exists($configFile)) {
            throw new Exception("The configuration file [ $configFile ] is not found.");
        }

        if (!is_readable($configFile)) {
            throw new Exception("The configuration file [ $configFile ] is not readable.");
        }

        ContextRegistry::getContext()->checkCacheDirectory();

        return $this->_getConfiguration($configFile);
    }

    /**#@-*/

    /**#@+
     * @access protected
     */

    /**#@-*/

    /**#@+
     * @access private
     */

    // }}}
    // {{{ _getConfiguration()

    /**
     * Gets a Piece::ORM::Config object from a cache.
     *
     * @param string $configFile
     * @return Piece::ORM::Config
     * @throws Piece::ORM::Exception::PEARException
     */
    private function _getConfiguration($configFile)
    {
        $configFile = realpath($configFile);
        $cache = new ::Cache_Lite_File(array('cacheDir' => ContextRegistry::getContext()->getCacheDirectory() . '/',
                                             'masterFile' => $configFile,
                                             'automaticSerialization' => true,
                                             'errorHandlingAPIBreak' => true)
                                       );

        if (!Env::isProduction()) {
            ::PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
            $cache->remove($configFile);
            ::PEAR::staticPopErrorHandling();
        }

        ::PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
        $config = $cache->get($configFile);
        ::PEAR::staticPopErrorHandling();
        if (::PEAR::isError($config)) {
            trigger_error('Cannot read the cache file in the directory [ ' .
                          ContextRegistry::getContext()->getCacheDirectory() .
                          ' ].',
                          E_USER_WARNING
                          );
        }

        if (!$config instanceof Config) {
            $config = $this->_readConfiguration($configFile);
            ::PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
            $result = $cache->save($config);
            ::PEAR::staticPopErrorHandling();
            if (::PEAR::isError($result)) {
                throw new PEARException($result);
            }
        }

        return $config;
    }

    // }}}
    // {{{ _readConfiguration()

    /**
     * Parses the given configuration file and returns a Piece::ORM::Config object.
     *
     * @param string $configFile
     * @return Piece::ORM::Config
     * @throws Piece::ORM::Exception
     */
    private function _readConfiguration($configFile)
    {
        $config = new Config();
        $dsl = ::Spyc::YAMLLoad($configFile);
        if (!is_array($dsl)) {
            return $config;
        }

        if (!array_key_exists('databases', $dsl)) {
            return $config;
        }

        foreach ($dsl['databases'] as $database => $configuration) {
            if (!array_key_exists('dsn', $configuration)) {
                throw new Exception("The element [ dsn ] is required in [ $configFile ].");
            }

            if (!is_array($configuration['dsn']) && !strlen($configuration['dsn'])) {
                throw new Exception("The value of the element [ dsn ] is required in [ $configFile ].");
            }

            $config->setDSN($database, $configuration['dsn']);

            if (array_key_exists('options', $configuration)) {
                if (!is_array($configuration['options'])) {
                    throw new Exception("The value of the element [ options ] must be an array in [ $configFile ].");
                }

                $config->setOptions($database, $configuration['options']);
            }

            if (array_key_exists('directorySuffix', $configuration)) {
                if (!strlen($configuration['directorySuffix'])) {
                    throw new Exception("The value of the element [ directorySuffix ] is required in [ $configFile ].");
                }

                $config->setDirectorySuffix($database, @$configuration['directorySuffix']);
            }

            if (array_key_exists('useMapperNameAsTableName', $configuration)) {
                if (!is_bool($configuration['useMapperNameAsTableName'])) {
                    throw new Exception("The value of the element [ useMapperNameAsTableName ] must be a boolean in [ $configFile ].");
                }

                $config->setUseMapperNameAsTableName($database, $configuration['useMapperNameAsTableName']);
            }
        }

        return $config;
    }

    /**#@-*/

    // }}}
}

// }}}

/*
 * Local Variables:
 * mode: php
 * coding: iso-8859-1
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * indent-tabs-mode: nil
 * End:
 */
