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

namespace Piece::ORM::Mapper;

use Piece::ORM::Inflector;
use Piece::ORM::Metadata::MetadataFactory;
use Piece::ORM::Exception;
use Piece::ORM::Env;
use Piece::ORM::Exception::PEARException;
use Piece::ORM::Mapper::Generator;
use Piece::ORM::Mapper::AbstractMapper;
use Piece::ORM::Context::ContextRegistry;

require_once 'spyc.php5';

// {{{ Piece::ORM::Mapper::MapperFactory

/**
 * A factory class for creating mapper objects.
 *
 * @package    Piece_ORM
 * @copyright  2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 0.1.0
 */
class MapperFactory
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
     * Creates a mapper object for a given mapper name.
     *
     * @param string $mapperName
     * @return Piece::ORM::Mapper::AbstractMapper
     * @throws Piece::ORM::Exception
     */
    public static function factory($mapperName)
    {
        $context = ContextRegistry::getContext();
        if (!$context->getUseMapperNameAsTableName()) {
            $mapperName = Inflector::camelize($mapperName);
        }

        $mapperID = "{$mapperName}_" . sha1($context->getDSN() . ".$mapperName." . realpath(self::getConfigDirectory()));
        $mapper = self::_getMapper($mapperID);
        if (is_null($mapper)) {
            self::_load($mapperID, $mapperName);
            $metadata = MetadataFactory::factory($mapperName);
            $mapperClass = __NAMESPACE__ . "::$mapperID";
            $mapper = new $mapperClass($metadata, $mapperID);
            if (!$mapper instanceof AbstractMapper) {
                throw new Exception("The mapper class for [ $mapperName ] is invalid.");
            }

            self::_addMapper($mapper);
        }

        $mapper->setConnection($context->getConnection());
        return $mapper;
    }

    // }}}
    // {{{ setConfigDirectory()

    /**
     * Sets a configuration directory.
     *
     * @param string $configDirectory
     */
    public static function setConfigDirectory($configDirectory)
    {
        $configDirectoryStack = self::_getConfigDirectoryStack();
        array_push($configDirectoryStack, $configDirectory);
        self::_setConfigDirectoryStack($configDirectoryStack);
    }

    // }}}
    // {{{ restoreConfigDirectory()

    /**
     * Restores the previous configuration directory.
     *
     * @since Method available since Release 2.0.0
     */
    public static function restoreConfigDirectory()
    {
        $configDirectoryStack = self::_getConfigDirectoryStack();
        array_pop($configDirectoryStack);
        self::_setConfigDirectoryStack($configDirectoryStack);
    }

    // }}}
    // {{{ getConfigDirectory()

    /**
     * Gets the config directory for the current context.
     *
     * @return array
     * @since Method available since Release 2.0.0
     */
    public function getConfigDirectory()
    {
        $configDirectoryStack = self::_getConfigDirectoryStack();
        if (!count($configDirectoryStack)) {
            return;
        }

        return $configDirectoryStack[ count($configDirectoryStack) - 1 ];
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
    // {{{ _getMapperSource()

    /**
     * Gets a mapper source by either generating from a configuration file or getting
     * from a cache.
     *
     * @param string $mapperID
     * @param string $mapperName
     * @param string $configFile
     * @return string
     * @throws Piece::ORM::Exception
     * @throws Piece::ORM::Exception::PEARException
     */
    private function _getMapperSource($mapperID, $mapperName, $configFile)
    {
        $cache = new ::Cache_Lite_File(array('cacheDir' => ContextRegistry::getContext()->getCacheDirectory() . '/',
                                             'masterFile' => $configFile,
                                             'automaticSerialization' => true,
                                             'errorHandlingAPIBreak' => true)
                                       );

        if (!Env::isProduction()) {
            $cache->remove($mapperID);
        }

        /*
         * The Cache_Lite class always specifies PEAR_ERROR_RETURN when
         * calling PEAR::raiseError in default.
         */
        $mapperSource = $cache->get($mapperID);
        if (::PEAR::isError($mapperSource)) {
            throw new Exception('Cannot read the mapper source file in the directory [ ' .
                                ContextRegistry::getContext()->getCacheDirectory() . 
                                ' ].'
                                );
        }

        if (!$mapperSource) {
            $mapperSource = self::_generateMapperSource($mapperID, $mapperName, $configFile);
            $result = $cache->save($mapperSource);
            if (::PEAR::isError($result)) {
                throw new PEARException($result);
            }
        }

        return $mapperSource;
    }

    // }}}
    // {{{ _generateMapperSource()

    /**
     * Generates a mapper source from the given configuration file.
     *
     * @param string $mapperID
     * @param string $mapperName
     * @param string $configFile
     * @return string
     */
    private function _generateMapperSource($mapperID, $mapperName, $configFile)
    {
        $generator = new Generator($mapperID,
                                   $mapperName,
                                   $configFile,
                                   MetadataFactory::factory($mapperName),
                                   get_class_methods('Piece::ORM::Mapper::AbstractMapper')
                                   );
        return $generator->generate();
    }

    // }}}
    // {{{ _loaded()

    /**
     * Returns whether or not the mapper class for a given mapper ID has already been
     * loaded.
     *
     * @param string $mapperID
     * @return boolean
     */
    private function _loaded($mapperID)
    {
        return class_exists(__NAMESPACE__ . "::$mapperID", false);
    }

    // }}}
    // {{{ _load()

    /**
     * Loads a mapper class based on the given information.
     *
     * @param string $mapperID
     * @param string $mapperName
     * @throws Piece::ORM::Exception
     */
    private function _load($mapperID, $mapperName)
    {
        if (self::_loaded($mapperID)) {
            return;
        }

        if (is_null(self::getConfigDirectory())) {
            throw new Exception('The configuration directory must be specified.');
        }

        if (!file_exists(self::getConfigDirectory())) {
            throw new Exception('The configuration directory [ ' .
                                self::getConfigDirectory() .
                                ' ] is not found.'
                                );
        }

        if (is_null(ContextRegistry::getContext()->getCacheDirectory())) {
            throw new Exception('The cache directory must be specified.');
        }

        if (!file_exists(ContextRegistry::getContext()->getCacheDirectory())) {
            throw new Exception('The cache directory [ ' .
                                ContextRegistry::getContext()->getCacheDirectory() .
                                'is not found.'
                                );
        }

        if (!is_readable(ContextRegistry::getContext()->getCacheDirectory())
            || !is_writable(ContextRegistry::getContext()->getCacheDirectory())
            ) {
            throw new Exception('The cache directory [ ' .
                                ContextRegistry::getContext()->getCacheDirectory() .
                                ' ] is not readable or writable.'
                                );
        }

        $configFile = self::getConfigDirectory() . "/$mapperName.yaml";
        if (!file_exists($configFile)) {
            throw new Exception("The configuration file [ $configFile ] is not found.");
        }

        if (!is_readable($configFile)) {
            throw new Exception("The configuration file [ $configFile ] is not readable.");
        }

        $mapperSource = self::_getMapperSource($mapperID, $mapperName, $configFile);
        eval($mapperSource);

        if (!self::_loaded($mapperID)) {
            throw new Exception("The mapper [ $mapperName ] not found.");
        }
    }

    // }}}
    // {{{ _getMapper()

    /**
     * Gets a Piece::ORM::Mapper::AbstractMapper object from the current context.
     *
     * @param string $mapperID
     * @return Piece::ORM::Mapper::AbstractMapper
     * @since Method available since Release 2.0.0
     */
    private static function _getMapper($mapperID)
    {
        $mapperRegistry = self::_getMapperRegistry();
        if (!array_key_exists($mapperID, $mapperRegistry)) {
            return;
        }

        return $mapperRegistry[$mapperID];
    }

    // }}}
    // {{{ _addMapper()

    /**
     * Adds a Piece::ORM::Mapper object to the current context.
     *
     * @param Piece::ORM::Mapper::AbstractMapper $mapper
     * @since Method available since Release 2.0.0
     */
    private static function _addMapper(AbstractMapper $mapper)
    {
        $mapperRegistry = self::_getMapperRegistry();
        $mapperRegistry[ $mapper->mapperID ] = $mapper;
        self::_setMapperRegistry($mapperRegistry);
    }

    // }}}
    // {{{ _getMapperRegistry()

    /**
     * Gets the mapper registry from the current context.
     *
     * @return array
     * @since Method available since Release 2.0.0
     */
    private function _getMapperRegistry()
    {
        if (!ContextRegistry::getContext()->hasAttribute(__CLASS__ . '::mapperRegistry')) {
            return array();
        }

        return ContextRegistry::getContext()->getAttribute(__CLASS__ . '::mapperRegistry');
    }

    // }}}
    // {{{ _setMapperRegistry()

    /**
     * Sets the mapper registry to the current context.
     *
     * @param array $mapperRegistry
     * @since Method available since Release 2.0.0
     */
    private function _setMapperRegistry(array $mapperRegistry)
    {
        ContextRegistry::getContext()->setAttribute(__CLASS__ . '::mapperRegistry', $mapperRegistry);
    }

    // }}}
    // {{{ _getConfigDirectoryStack()

    /**
     * Gets the config directory stack from the current context.
     *
     * @return array
     * @since Method available since Release 2.0.0
     */
    private function _getConfigDirectoryStack()
    {
        if (!ContextRegistry::getContext()->hasAttribute(__CLASS__ . '::configDirectoryStack')) {
            return array();
        }

        return ContextRegistry::getContext()->getAttribute(__CLASS__ . '::configDirectoryStack');
    }

    // }}}
    // {{{ _setConfigDirectoryStack()

    /**
     * Sets the config directory stack to the current context.
     *
     * @param array $configDirectoryStack
     * @since Method available since Release 2.0.0
     */
    private function _setConfigDirectoryStack(array $configDirectoryStack)
    {
        ContextRegistry::getContext()->setAttribute(__CLASS__ . '::configDirectoryStack', $configDirectoryStack);
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