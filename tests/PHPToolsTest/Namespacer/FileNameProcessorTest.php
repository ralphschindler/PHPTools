<?php

namespace PHPToolsTest\Namespacer;
use PHPTools\Namespacer\FileNameProcessor as FileNameProcessor;

class FileNameProcessorTest extends \PHPUnit_Framework_TestCase
{
    
    public function testOriginalValuesAreComputedAndPreservedWorks()
    {
        $fnc = new FileNameProcessor('Zend/Validate/Alpha.php', './foo/bar');
        
        $this->assertEquals('./foo/bar/Zend/Validate/Alpha.php', $fnc->getOriginalFilePath(), 'Original file path not preserved.');
        $this->assertEquals('Zend/Validate/Alpha.php', $fnc->getOriginalRelativeFilePath(), 'Original file path not preserved.');
        $this->assertEquals('Zend_Validate_Alpha', $fnc->getOriginalClassName(), 'Original class not computed or preserved.');
    }
    
    public function testAbstractNamingWorks()
    {
        $fnc = new FileNameProcessor('Zend/Validate/Abstract.php', './foo/bar');
        
        $this->assertEquals('Zend\Validate', $fnc->getNewNamespace(), 'New namespace not computed correctly.');
        $this->assertEquals('AbstractValidate', $fnc->getNewClassName(), 'New class name not computed correctly.');
        $this->assertEquals('Zend\Validate\AbstractValidate', $fnc->getNewFullyQualifiedName(), 'New FQN not computed correctly.');
        $this->assertEquals('Zend/Validate/AbstractValidate.php', $fnc->getNewRelativeFilePath(), 'New file path not computed correctly.');
    }
    
    public function testClassMovedIntoNamespaceWorks()
    {
        $fnc = new FileNameProcessor('Zend/Filter.php', realpath(dirname(__FILE__) . '/_files/'));
        
        $this->assertEquals('Zend\Filter', $fnc->getNewNamespace(), 'New namespace not computed correctly.');
        $this->assertEquals('Filter', $fnc->getNewClassName(), 'New class name not computed correctly.');
        $this->assertEquals('Zend\Filter\Filter', $fnc->getNewFullyQualifiedName(), 'New FQN not computed correctly.');
        $this->assertEquals('Zend/Filter/Filter.php', $fnc->getNewRelativeFilePath(), 'New file path not computed correctly.');
    }
    
}