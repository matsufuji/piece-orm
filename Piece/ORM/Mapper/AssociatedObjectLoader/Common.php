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
 * @since      File available since Release 0.2.0
 */

// {{{ Piece_ORM_Mapper_AssociatedObjectLoader_Common

/**
 * The base class for associated object loaders.
 *
 * @package    Piece_ORM
 * @copyright  2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 0.2.0
 */
abstract class Piece_ORM_Mapper_AssociatedObjectLoader_Common
{

    // {{{ properties

    /**#@+
     * @access public
     */

    /**#@-*/

    /**#@+
     * @access protected
     */

    protected $useMultipleIndexes = false;
    protected $defaultValueOfMappedAs;
    protected $relationships;
    protected $relationshipKeys;
    protected $objects;
    protected $objectIndexes;

    /**#@-*/

    /**#@+
     * @access private
     */

    private $_mapper;

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ __construct()

    /**
     * Initializes properties with the given value.
     *
     * @param array                   $relationships
     * @param array                   &$relationshipKeys
     * @param array                   &$objects
     * @param array                   &$objectIndexes
     * @param Piece_ORM_Mapper_Common $mapper
     */
    public function __construct(array $relationships,
                                array &$relationshipKeys,
                                array &$objects,
                                array &$objectIndexes,
                                Piece_ORM_Mapper_Common $mapper
                                )
    {
        $this->relationships = $relationships;
        $this->relationshipKeys = &$relationshipKeys;
        $this->objects = &$objects;
        $this->objectIndexes = &$objectIndexes;
        $this->_mapper = $mapper;
    }

    // }}}
    // {{{ prepareLoading()

    /**
     * Prepares loading associated objects.
     *
     * @param array   $row
     * @param integer $objectIndex
     * @param integer $relationshipIndex
     */
    public function prepareLoading(array $row, $objectIndex, $relationshipIndex)
    {
        $relationshipKeyFieldName = $this->getRelationshipKeyFieldNameInPrimaryQuery($this->relationships[$relationshipIndex]);
        $this->objects[$objectIndex]->{ $this->relationships[$relationshipIndex]['mappedAs'] } = $this->defaultValueOfMappedAs;

        $this->relationshipKeys[$relationshipIndex][] = $this->_mapper->quote($row[$relationshipKeyFieldName], $relationshipKeyFieldName);

        if (!$this->useMultipleIndexes) {
            $this->objectIndexes[$relationshipIndex][ $row[$relationshipKeyFieldName] ] = $objectIndex;
        } else {
            $this->objectIndexes[$relationshipIndex][ $row[$relationshipKeyFieldName] ][] = $objectIndex;
        }
    }

    // }}}
    // {{{ loadAll()

    /**
     * Loads all associated objects into appropriate objects.
     *
     * @param Piece_ORM_Mapper_Common $mapper
     * @param integer                 $relationshipIndex
     */
    public function loadAll(Piece_ORM_Mapper_Common $mapper, $relationshipIndex)
    {
        $mapper->setPreloadCallback($this->getPreloadCallback());
        $mapper->setPreloadCallbackArgs(array($relationshipIndex));
        $associatedObjects = $mapper->findAllWithQuery($this->buildQuery($relationshipIndex) . (is_null($this->relationships[$relationshipIndex]['orderBy']) ? '' : " ORDER BY {$this->relationships[$relationshipIndex]['orderBy']}"));
        $mapper->setPreloadCallback(null);
        $mapper->setPreloadCallbackArgs(null);

        $relationshipKeyPropertyName = Piece_ORM_Inflector::camelize($this->getRelationshipKeyFieldNameInSecondaryQuery($this->relationships[$relationshipIndex]), true);

        for ($j = 0, $count = count($associatedObjects); $j < $count; ++$j) {
            $this->associateObject($associatedObjects[$j], $mapper, $relationshipKeyPropertyName, $relationshipIndex);
        }
    }

    /**#@-*/

    /**#@+
     * @access protected
     */

    // }}}
    // {{{ buildQuery()

    /**
     * Builds a query to get associated objects.
     *
     * @param integer $relationshipIndex
     * @return string
     */
    abstract protected function buildQuery($relationshipIndex);

    // }}}
    // {{{ getRelationshipKeyFieldNameInPrimaryQuery()

    /**
     * Gets the name of the relationship key field in the primary query.
     *
     * @param array $relationship
     */
    abstract protected function getRelationshipKeyFieldNameInPrimaryQuery(array $relationship);

    // }}}
    // {{{ getRelationshipKeyFieldNameInSecondaryQuery()

    /**
     * Gets the name of the relationship key field in the secondary query.
     *
     * @param array $relationship
     */
    abstract protected function getRelationshipKeyFieldNameInSecondaryQuery(array $relationship);

    // }}}
    // {{{ associateObject()

    /**
     * Associates an object which are loaded by the secondary query into objects which
     * are loaded by the primary query.
     *
     * @param stdClass                $associatedObject
     * @param Piece_ORM_Mapper_Common $mapper
     * @param string                  $relationshipKeyPropertyName
     * @param integer                 $relationshipIndex
     */
    abstract protected function associateObject($associatedObject,
                                                Piece_ORM_Mapper_Common $mapper,
                                                $relationshipKeyPropertyName,
                                                $relationshipIndex
                                                );

    // }}}
    // {{{ getPreloadCallback()

    /**
     * Gets the preload callback for a loader.
     *
     * @return callback
     */
    protected function getPreloadCallback()
    {
        return null;
    }

    /**#@-*/

    /**#@+
     * @access private
     */

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
