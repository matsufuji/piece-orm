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

require_once 'spyc.php5';

// {{{ ConfigFactory

/**
 * A factory class for creating Piece::ORM::Config objects.
 *
 * @package    Piece_ORM
 * @copyright  2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 0.1.0
 */
class ConfigFactory
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

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ factory()

    /**
     * Creates a Piece::ORM::Config object from a configuration file or a cache.
     *
     * @param string $configDirectory
     * @param string $cacheDirectory
     * @return Piece::ORM::Config
     * @throws Piece::ORM::Exception
     */
    public static function factory($configDirectory = null, $cacheDirectory = null)
    {
        if (is_null($configDirectory)) {
            return new Config();
        }

        if (!file_exists($configDirectory)) {
            throw new Exception("The configuration directory [ $configDirectory ] is not found.");
        }

        $configFile = "$configDirectory/piece-orm-config.yaml";
        if (!file_exists($configFile)) {
            throw new Exception("The configuration file [ $configFile ] is not found.");
        }

        if (!is_readable($configFile)) {
            throw new Exception("The configuration file [ $configFile ] is not readable.");
        }

        if (is_null($cacheDirectory)) {
            return self::_createConfigurationFromFile($configFile);
        }

        if (!file_exists($cacheDirectory)) {
            trigger_error("The cache directory [ $cacheDirectory ] is not found.",
                          E_USER_WARNING
                          );
            return self::_createConfigurationFromFile($configFile);
        }

        if (!is_readable($cacheDirectory) || !is_writable($cacheDirectory)) {
            trigger_error("The cache directory [ $cacheDirectory ] is not readable or writable.",
                          E_USER_WARNING
                          );
            return self::_createConfigurationFromFile($configFile);
        }

        return self::_getConfiguration($configFile, $cacheDirectory);
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
     * @param string $masterFile
     * @param string $cacheDirectory
     * @return Piece::ORM::Config
     */
    private function _getConfiguration($masterFile, $cacheDirectory)
    {
        $masterFile = realpath($masterFile);
        $cache = new ::Cache_Lite_File(array('cacheDir' => "$cacheDirectory/",
                                             'masterFile' => $masterFile,
                                             'automaticSerialization' => true,
                                             'errorHandlingAPIBreak' => true)
                                       );

        if (!Env::isProduction()) {
            $cache->remove($masterFile);
        }

        /*
         * The Cache_Lite class always specifies PEAR_ERROR_RETURN when
         * calling ::PEAR::raiseError in default.
         */
        $config = $cache->get($masterFile);
        if (::PEAR::isError($config)) {
            trigger_error("Cannot read the cache file in the directory [ $cacheDirectory ].",
                          E_USER_WARNING
                          );
            return self::_createConfigurationFromFile($masterFile);
        }

        if (!$config) {
            $config = self::_createConfigurationFromFile($masterFile);
            $result = $cache->save($config);
            if (::PEAR::isError($result)) {
                trigger_error("Cannot write the Piece::ORM::Config object to the cache file in the directory [ $cacheDirectory ].",
                              E_USER_WARNING
                              );
            }
        }

        return $config;
    }

    // }}}
    // {{{ _createConfigurationFromFile()

    /**
     * Parses the given file and returns a Piece::ORM::Config object.
     *
     * @param string $file
     * @return Piece::ORM::Config
     */
    private function _createConfigurationFromFile($file)
    {
        $config = new Config();
        $yaml = ::Spyc::YAMLLoad($file);
        foreach ($yaml as $configuration) {
            $config->setDSN($configuration['name'], $configuration['dsn']);
            $config->setOptions($configuration['name'], @$configuration['options']);
            $config->setDirectorySuffix($configuration['name'], @$configuration['directorySuffix']);
            $config->setUseMapperNameAsTableName($configuration['name'], @$configuration['useMapperNameAsTableName']);
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