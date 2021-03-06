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

namespace Piece::ORM::Mapper::ObjectLoader::AssociationLoaderStrategy;

use Piece::ORM::Mapper::ObjectLoader::AssociationLoaderStrategy::AbstractAssociationLoaderStrategy;
use Piece::ORM::Mapper::Association;
use Piece::ORM::Mapper;

// {{{ Piece::ORM::Mapper::ObjectLoader::AbstractAssociationLoaderStrategy::OneToOne

/**
 * An associated object loader for One-to-One relationships.
 *
 * @package    Piece_ORM
 * @copyright  2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 0.2.0
 */
class OneToOne extends AbstractAssociationLoaderStrategy
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

    /**#@-*/

    /**#@+
     * @access protected
     */

    // }}}
    // {{{ _buildQuery()

    /**
     * Builds a query to get associated objects.
     *
     * @param integer $associationIndex
     * @return string
     */
    protected function _buildQuery($associationIndex)
    {
        return 'SELECT * FROM ' .
            $this->associations[$associationIndex]->getTable() .
            ' WHERE ' .
            $this->associations[$associationIndex]->getColumn() .
            ' IN (' .
            implode(',', $this->associationKeys[$associationIndex]) .
            ')';
    }

    // }}}
    // {{{ _getAssociationKeyFieldInPrimaryQuery()

    /**
     * Gets the name of the association key field in the primary query.
     *
     * @param Piece::ORM::Mapper::Association $association
     */
    protected function _getAssociationKeyFieldInPrimaryQuery(Association $association)
    {
        return $association->getReferencedColumn();
    }

    // }}}
    // {{{ _getAssociationKeyFieldInSecondaryQuery()

    /**
     * Gets the name of the association key field in the secondary query.
     *
     * @param Piece::ORM::Mapper::Association $association
     */
    protected function _getAssociationKeyFieldInSecondaryQuery(Association $association)
    {
        return $association->getColumn();
    }

    // }}}
    // {{{ _associateObject()

    /**
     * Associates an object which are loaded by the secondary query into objects which
     * are loaded by the primary query.
     *
     * @param stdClass           $associatedObject
     * @param Piece::ORM::Mapper $mapper
     * @param string             $associationKeyProperty
     * @param integer            $associationIndex
     */
    protected function _associateObject($associatedObject, Mapper $mapper, $associationKeyProperty, $associationIndex)
    {
        $this->objects[ $this->objectIndexes[$associationIndex][ $associatedObject->$associationKeyProperty ] ]->{ $this->associations[$associationIndex]->getProperty() } = $associatedObject;
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
