<?php

namespace PHPTools\Namespacer;

class FileNameProcessor
{
    
    protected $_libraryPath = null;
    protected $_originalRelativeFilePath = null;
    protected $_originalClassName = null;
    
    protected $_newRelativeFilePath = null;
    
    protected $_newNamespace = null;
    protected $_newClassName = null;
    protected $_newFullyQualifiedName = null;
    
    public function __construct($relativeFilePath, $libraryPath)
    {
        $this->_originalRelativeFilePath = $relativeFilePath;
        $this->_libraryPath = $libraryPath;
        $this->_process();
    }
    
    public function getOriginalFilePath()
    {
        return rtrim($this->_libraryPath, '/\\') . DIRECTORY_SEPARATOR . $this->_originalRelativeFilePath;
    }
    
    public function getOriginalRelativeFilePath()
    {
        return $this->_originalRelativeFilePath;
    }
    
    public function getOriginalClassName()
    {
        return $this->_originalClassName;
    }
    
    public function getLibraryPath()
    {
        return $this->_libraryPath;
    }
    
    public function getNewRelativeFilePath()
    {
        return $this->_newRelativeFilePath;
    }
    
    public function getNewFullyQualifiedName()
    {
        return $this->_newFullyQualifiedName;
    }
    
    public function getNewNamespace()
    {
        return $this->_newNamespace;
    }
    
    public function getNewClassName()
    {
        return $this->_newClassName;
    }
    
    protected function _process()
    {
        // change separators to underscore
        $this->_originalClassName = str_replace(array('/', '\\'), '_', $this->_originalRelativeFilePath);
        $this->_originalClassName = substr($this->_originalClassName, 0, -4);
        
        $originalClassParts = explode('_', $this->_originalClassName);
        $newClassParts = $originalClassParts;
        
        // does this class have sub parts?
        if (is_dir($this->_libraryPath . '/' . implode('/', $newClassParts))) {
            $lastSegment = end($newClassParts);
            $newClassParts[] = $lastSegment;
        }
        
        $this->_newNamespace = implode('\\', array_slice($newClassParts, 0, count($newClassParts)-1));
        $this->_newClassName = end($newClassParts);
            
        if ($this->_newClassName === 'Abstract') {
            $this->_newClassName = 'Abstract' . 
                substr($this->_newNamespace, strrpos($this->_newNamespace, '\\') + 1);
        } elseif ($this->_newClassName === 'Interface') {
            $this->_newClassName = substr($this->_newNamespace, strrpos($this->_newNamespace, '\\') + 1)
                . 'Interface'; 
        }

        $this->_newFullyQualifiedName = $this->_newNamespace . '\\' . $this->_newClassName;
        $this->_newRelativeFilePath = str_replace('\\', '/', $this->_newFullyQualifiedName) . '.php';
        
    }
    
    public function __toString()
    {
        return $this->_originalClassName . ' => ' . $this->_newFullyQualifiedName;
    }
    
    
}