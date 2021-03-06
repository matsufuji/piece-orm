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

use Piece::ORM::Config::ConfigReader;
use Piece::ORM::Context;
use Piece::ORM::Context::ContextRegistry;

require_once 'spyc.php5';

// {{{ Piece::ORM::Config::ConfigReaderTest

/**
 * Some tests for Piece::ORM::Config::ConfigReader.
 *
 * @package    Piece_ORM
 * @copyright  2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 0.1.0
 */
class ConfigReaderTest extends ::PHPUnit_Framework_TestCase
{

    // {{{ properties

    /**#@+
     * @access public
     */

    /**#@-*/

    /**#@+
     * @access protected
     */

    protected $backupGlobals = false;

    /**#@-*/

    /**#@+
     * @access private
     */

    private $_cacheDirectory;

    /**#@-*/

    /**#@+
     * @access public
     */

    public function setUp()
    {
        $this->_cacheDirectory = dirname(__FILE__) . '/' . basename(__FILE__, '.php');
        ContextRegistry::setContext(new Context());
        ContextRegistry::getContext()->setCacheDirectory($this->_cacheDirectory);
    }

    public function tearDown()
    {
        ContextRegistry::getContext()->clearCache();
        ContextRegistry::clear();
    }

    public function testCreateAnObjectWithoutConfigurationFile()
    {
        $configReader = new ConfigReader();

        $this->assertType('Piece::ORM::Config', $configReader->read());
    }

    /**
     * @expectedException Piece::ORM::Exception
     */
    public function testRaiseAnExceptionWhenAGivenConfigurationDirectoryIsNotFound()
    {
        $configReader = new ConfigReader(dirname(__FILE__) . '/foo');
        $configReader->read();
    }

    /**
     * @expectedException Piece::ORM::Exception
     */
    public function testRaiseAnExceptionWhenAGivenConfigurationFileIsNotFound()
    {
        $configReader = new ConfigReader(dirname(__FILE__));
        $configReader->read();
    }

    public function testCreateAnObjectEvenThoughAGivenCacheDirectoryIsNotFound()
    {
        $configReader = new ConfigReader($this->_cacheDirectory);

        $this->assertType('Piece::ORM::Config', $configReader->read());
    }

    public function testCreateAnObjectByAGivenConfigurationFile()
    {
        $dsl = ::Spyc::YAMLLoad("{$this->_cacheDirectory}/piece-orm-config.yaml");
        $configReader = new ConfigReader($this->_cacheDirectory);
        $config = $configReader->read();

        $this->assertEquals(2, count($dsl['databases']));

        foreach ($dsl['databases'] as $database => $configuration) {
            $this->assertEquals($configuration['dsn'], $config->getDSN($database));
            $this->assertEquals($configuration['options'],
                                $config->getOptions($database)
                                );
        }
    }

    /**
     * @since Method available since Release 0.8.0
     */
    public function testCreateUniqueCacheIdsInOneCacheDirectory()
    {
        $oldDirectory = getcwd();
        chdir("{$this->_cacheDirectory}/CacheIDsShouldBeUniqueInOneCacheDirectory1");
        $configReader = new ConfigReader('.');
        $configReader->read();

        $this->assertEquals(1, $this->_getCacheFileCount($this->_cacheDirectory));

        chdir("{$this->_cacheDirectory}/CacheIDsShouldBeUniqueInOneCacheDirectory2");
        $configReader = new ConfigReader('.');
        $configReader->read();

        $this->assertEquals(2, $this->_getCacheFileCount($this->_cacheDirectory));

        chdir($oldDirectory);
    }

    /**#@-*/

    /**#@+
     * @access protected
     */

    /**#@-*/

    /**#@+
     * @access private
     */

    /**
     * @since Method available since Release 0.8.0
     */
    private function _getCacheFileCount($directory)
    {
        $cacheFileCount = 0;
        if ($dh = opendir($directory)) {
            while (true) {
                $file = readdir($dh);
                if ($file === false) {
                    break;
                }

                if (filetype("$directory/$file") == 'file') {
                    if (preg_match('/^cache_.+/', $file)) {
                        ++$cacheFileCount;
                    }
                }
            }

            closedir($dh);
        }

        return $cacheFileCount;
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
