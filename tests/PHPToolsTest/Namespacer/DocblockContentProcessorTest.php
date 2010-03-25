<?php

namespace PHPToolsTest\Namespacer;
use PHPTools\Namespacer\DocblockContentProcessor as DocblockContentProcessor;

class DocblockContentProcessorTest extends \PHPUnit_Framework_TestCase
{
    
    public function setup()
    {
        $this->_fileRegistry = new \PHPTools\Namespacer\FileRegistry;
        
        $libraryDirectory = realpath(dirname(__FILE__) . '/_files/');
        
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($libraryDirectory)) as $realFilePath => $fileInfo) {
            $relativeFilePath = substr($realFilePath, strlen($libraryDirectory)+1);
            $fileNameProcessor = new \PHPTools\Namespacer\FileNameProcessor($relativeFilePath, $libraryDirectory);
            $this->_fileRegistry->registerFileNameProcessor($fileNameProcessor);
        }

    }
    
    public function teardown()
    {
        $this->_fileRegistry = null;
    }
    
    public function testTrue()
    {
        $docblock = <<<EOS
/**
 * Some Text
 * 
 * Foo
 *
 * @uses Zend_Filter
 * @uses     Zend_Filter_Alpha
 * @param Zend_Filter \$filter
 * @param string \$foo
 *
 * @return bool
 */
EOS;
        
        
        $expected = <<<EOS
/**
 * Some Text
 * 
 * Foo
 *
 * @uses \Zend\Filter\Filter
 * @uses     \Zend\Filter\Alpha
 * @param \Zend\Filter\Filter \$filter
 * @param string \$foo
 *
 * @return bool
 */
EOS;
        $dcp = new DocblockContentProcessor($docblock, array('Zend'), $this->_fileRegistry);
        $this->assertEquals($expected, $dcp->getContents());
    }

}
