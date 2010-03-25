<?php

namespace PHPTools\Namespacer;

class RecursiveFilterIterator extends \RecursiveFilterIterator
{
    
    protected $_filter = null;
    protected $_topDirectory = null;

    /**
     * constructor
     *
     * @param RecursiveIterator $iterator
     * @param string $filter
     */
    public function __construct(\RecursiveIterator $iterator, $filter, $topDirectory = null)
    {
        $this->_filter = $filter;
        if ($topDirectory == null) {
            $iterator->rewind();
            $this->_topDirectory = (string) $iterator->current()->getPath();
        } else {
            $this->_topDirectory = $topDirectory;
        }
        parent::__construct($iterator);
    }
    
    
    public function accept()
    {
        if ($this->isDot()) {
            return false;
        }
        
        if ($this->isDir()) {
            return true;
        }
        
        $relativeFileName = substr($this->current()->getRealPath(), strlen($this->_topDirectory)+1);
        if (preg_match('#^' . preg_quote($this->_filter) . '#', $relativeFileName)) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * getChildren() - overridden from RecursiveFilterIterator to allow the persistence of
     * the $_denyDirectoryPattern and the $_acceptFilePattern when sub iterators of this filter
     * are needed to be created.
     *
     * @return Zend_Tool_Framework_Loader_IncludePathLoader_RecursiveFilterIterator
     */
    public function getChildren()
    {
        if (empty($this->ref)) {
            $this->ref = new \ReflectionClass($this);
        }

        return $this->ref->newInstance(
            $this->getInnerIterator()->getChildren(),
            $this->_filter,
            $this->_topDirectory
            );
    }
    
    
}