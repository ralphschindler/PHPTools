<?php

namespace PHPTools\Namespacer;

class FileNameProcessor
{
    
    protected $_libraryDirectory = null;
    protected $_originalRelativeFilePath = null;
    protected $_originalClassName = null;
    
    protected $_newRelativeFilePath = null;
    
    protected $_newNamespace = null;
    protected $_newClassName = null;
    protected $_newFullyQualifiedName = null;
    
    public function __construct($relativeFilePath, $libraryDirectory)
    {
        $this->_libraryDirectory = $libraryDirectory;
        
        if (is_string($relativeFilePath)) {
            $this->_originalRelativeFilePath = $relativeFilePath;
            $this->_process();
        } elseif (is_array($relativeFilePath)) {
            $options = $relativeFilePath;
            $this->_originalRelativeFilePath = $options['originalRelativeFilePath'];
            $this->_originalClassName        = $options['originalClassName'];
            $this->_newRelativeFilePath      = $options['newRelativeFilePath'];
            $this->_newNamespace             = $options['newNamespace'];
            $this->_newClassName             = $options['newClassName'];
            $this->_newFullyQualifiedName    = $options['newFullyQualifiedName']; 
        }
    }
    
    public function getOriginalFilePath()
    {
        return rtrim($this->_libraryDirectory, '/\\') . DIRECTORY_SEPARATOR . $this->_originalRelativeFilePath;
    }
    
    public function getOriginalRelativeFilePath()
    {
        return $this->_originalRelativeFilePath;
    }
    
    public function getOriginalClassName()
    {
        return $this->_originalClassName;
    }
    
    public function getLibraryDirectory()
    {
        return $this->_libraryDirectory;
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
        if (is_dir($this->_libraryDirectory . '/' . implode('/', $newClassParts))) {
            $lastSegment = end($newClassParts);
            $newClassParts[] = $lastSegment;
        }
        
        $this->_newNamespace = implode('\\', array_slice($newClassParts, 0, count($newClassParts)-1));
        
        $lastClassPart = end($newClassParts);
        $this->_newClassName = $this->_createSafeClassName($lastClassPart);


        $this->_newFullyQualifiedName = $this->_newNamespace . '\\' . $this->_newClassName;
        $this->_newRelativeFilePath = str_replace('\\', '/', $this->_newFullyQualifiedName) . '.php';
        
    }
    
    protected function _createSafeClassName($className)
    {
        if ($className === 'Abstract') {
            $className = 'Abstract' . substr($this->_newNamespace, strrpos($this->_newNamespace, '\\') + 1);
        } elseif ($className === 'Interface') {
            $className = substr($this->_newNamespace, strrpos($this->_newNamespace, '\\') + 1) . 'Interface';
        }
        
        $reservedWords = array(
            'and','array','as','break','case','catch','class','clone',
            'const','continue','declare','default','do','else','elseif',
            'enddeclare','endfor','endforeach','endif','endswitch','endwhile',
            'extends','final','for','foreach','function','global',
            'goto','if','implements','instanceof','namespace',
            'new','or','private','protected','public','static','switch',
            'throw','try','use','var','while','xor'
            );
        
        if (in_array($className, $reservedWords)) {
            $className = $className . 'CLASS';
        }
        
        return $className;
    }
    
    public function __toString()
    {
        return $this->_originalClassName . ' => ' . $this->_newFullyQualifiedName;
    }
    
    
}