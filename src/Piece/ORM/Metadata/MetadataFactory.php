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

namespace Piece::ORM::Metadata;

use Piece::ORM::Inflector;
use Piece::ORM::Env;
use Piece::ORM::Exception::PEARException;
use Piece::ORM::Metadata::MetadataFactory::NoSuchTableException;
use Piece::ORM::MDB2::Decorator::Reverse::Mssql;
use Piece::ORM::Metadata;
use Piece::ORM::Context::ContextRegistry;
use Stagehand::Cache;

// {{{ Piece::ORM::Metadata::MetadataFactory

/**
 * A factory class to create a Piece::ORM::Metadata object for a table.
 *
 * @package    Piece_ORM
 * @copyright  2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 0.1.0
 */
class MetadataFactory
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
     * Creates a Piece::ORM::Metadata object for the given table.
     *
     * @param string $tableName
     * @return Piece::ORM::Metadata
     */
    public static function factory($tableName)
    {
        $context = ContextRegistry::getContext();
        if (!$context->getUseMapperNameAsTableName()) {
            $tableName = Inflector::underscore($tableName);
        }

        $tableID = sha1($context->getDSN() . ".$tableName");
        $metadata = self::_getMetadata($tableID);
        if (is_null($metadata)) {
            $metadata = self::_createMetadata($tableID, $tableName);
            self::_addMetadata($metadata);
        }

        return $metadata;
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
    // {{{ _loadMetadata()

    /**
     * Creates a Piece::ORM::Metadata object from a database.
     *
     * @param string $tableID
     * @param string $tableName
     * @return Piece::ORM::Metadata
     * @throws Piece::ORM::Exception::PEARException
     * @throws Piece::ORM::Metadata::Factory::NoSuchTableException
     */
    private static function _loadMetadata($tableID, $tableName)
    {
        $context = ContextRegistry::getContext();
        $dbh = $context->getConnection();

        ::PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
        $result = $dbh->setLimit(1);
        ::PEAR::staticPopErrorHandling();
        if (::MDB2::isError($result)) {
            throw new PEARException($result);
        }

        ::PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
        $result = $dbh->query('SELECT 1 FROM ' . $dbh->quoteIdentifier($tableName));
        ::PEAR::staticPopErrorHandling();
        if (::MDB2::isError($result)) {
            if ($result->getCode() == MDB2_ERROR_NOSUCHTABLE) {
                throw new NoSuchTableException($result);
            }

            throw new PEARException($result);
        }

        ::PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
        $reverse = $dbh->loadModule('Reverse');
        ::PEAR::staticPopErrorHandling();
        if (::MDB2::isError($reverse)) {
            throw new PEARException($reverse);
        }

        if ($dbh->phptype == 'mssql') {
            $reverse = new Mssql($reverse);
        }

        ::PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
        $tableInfo = $reverse->tableInfo($tableName);
        ::PEAR::staticPopErrorHandling();
        if (::MDB2::isError($tableInfo)) {
            throw new PEARException($tableInfo);
        }

        if ($dbh->phptype == 'mysql') {
            foreach (array_keys($tableInfo) as $fieldName) {
                if ($tableInfo[$fieldName]['nativetype'] == 'datetime'
                    && $tableInfo[$fieldName]['notnull']
                    && $tableInfo[$fieldName]['default'] == '0000-00-00 00:00:00'
                    ) {
                    $tableInfo[$fieldName]['flags'] =
                        str_replace('default_0000-00-00%2000%3A00%3A00',
                                    '',
                                    $tableInfo[$fieldName]['flags']
                                    );
                    $tableInfo[$fieldName]['default'] = '';
                }
            }
        }

        return new Metadata($tableInfo, $tableID);
    }

    // }}}
    // {{{ _createMetadata()

    /**
     * Creates a Piece::ORM::Metadata object from a cache or a database.
     *
     * @param string $tableID
     * @param string $tableName
     * @return Piece::ORM::Metadata
     * @throws Piece::ORM::Exception
     */
    private static function _createMetadata($tableID, $tableName)
    {
        ContextRegistry::getContext()->checkCacheDirectory();

        $cache = new Cache(ContextRegistry::getContext()->getCacheDirectory());

        if (!Env::isProduction()) {
            $cache->remove($tableID);
        }

        try {
            $metadata = $cache->read($tableID);
        } catch (Stagehand::Cache::Exception $e) {
            throw new Exception($e->getMessage());
        }

        if (is_null($metadata)) {
            $metadata = self::_loadMetadata($tableID, $tableName);
            try {
                $cache->write($metadata);
            } catch (Stagehand::Cache::Exception $e) {
                throw new Exception($e->getMessage());
            }
        }

        return $metadata;
    }

    // }}}
    // {{{ _getMetadata()

    /**
     * Gets a Piece::ORM::Metadata object from the current context.
     *
     * @param string $tableID
     * @return Piece::ORM::Metadata
     */
    private static function _getMetadata($tableID)
    {
        $metadataRegistry = self::_getMetadataRegistry();
        if (!array_key_exists($tableID, $metadataRegistry)) {
            return;
        }

        return $metadataRegistry[$tableID];
    }

    // }}}
    // {{{ _addMetadata()

    /**
     * Adds a Piece::ORM::Metadata object to the current context.
     *
     * @param Piece::ORM::Metadata $metadata
     */
    private static function _addMetadata(Metadata $metadata)
    {
        $metadataRegistry = self::_getMetadataRegistry();
        $metadataRegistry[ $metadata->tableID ] = $metadata;
        self::_setMetadataRegistry($metadataRegistry);
    }

    // }}}
    // {{{ _getMetadataRegistry()

    /**
     * Gets the metadata registry from the current context.
     *
     * @return array
     */
    private function _getMetadataRegistry()
    {
        if (!ContextRegistry::getContext()->hasAttribute(__CLASS__ . '::metadataRegistry')) {
            return array();
        }

        return ContextRegistry::getContext()->getAttribute(__CLASS__ . '::metadataRegistry');
    }

    // }}}
    // {{{ _setMetadataRegistry()

    /**
     * Sets the metadata registry to the current context.
     *
     * @param array $metadataRegistry
     */
    private function _setMetadataRegistry(array $metadataRegistry)
    {
        ContextRegistry::getContext()->setAttribute(__CLASS__ . '::metadataRegistry', $metadataRegistry);
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
