<?php

namespace PHPTools\Namespacer;

class FileRegistry implements \IteratorAggregate, \Countable
{

    const ITERATE_NAMES = 'iterateNames';
    const ITERATE_CONTENTS = 'iterateContents';
    
    protected $_fileNameProcessors = array();
    protected $_fileContentProcessors = array();
    
    protected $_iterationType = self::ITERATE_NAMES;

    protected $_fileNameByFilePathIndex = array();
    protected $_fileNameByRelativeFilePathIndex = array();
    protected $_fileNameByOriginalClassNameIndex = array();
    protected $_fileContentProcessorToFileNameProcessorIndex = array();
    
    public function registerFileNameProcessor(FileNameProcessor $fileNameProcessor)
    {
        $objectId = spl_object_hash($fileNameProcessor);
        $this->_fileNameProcessors[$objectId] = $fileNameProcessor;
        
        $this->_fileNameByOriginalFilePathIndex[$fileNameProcessor->getOriginalFilePath()] = $objectId;
        $this->_fileNameByOriginalRelativeFilePathIndex[$fileNameProcessor->getOriginalRelativeFilePath()] = $objectId;
        $this->_fileNameByOriginalClassNameIndex[$fileNameProcessor->getOriginalClassName()] = $objectId;
        $this->_fileNameByNewFullyQualifiedNameIndex[$fileNameProcessor->getNewFullyQualifiedName()] = $objectId;
        
        return $this;
    }
    
    public function registerFileContentProcessor(FileContentProcessor $fileContentProcessor)
    {
        $objectId = spl_object_hash($fileContentProcessor);
        $this->_fileContentProcessors[$objectId] = $fileContentProcessor;
        
        $fileNameProcessorObjectId = spl_object_hash($fileContentProcessor->getFileNameProcessor());
        $this->_fileContentProcessorToFileNameProcessorIndex[$objectId] = $fileNameProcessorObjectId;
    }

    public function findByOriginalFilePath($originalFilePath)
    {
        if (array_key_exists($originalFilePath, $this->_fileNameByOriginalFilePathIndex)) {
            $objId = $this->_fileNameByOriginalFilePathIndex[$originalFilePath];
            if ($objId && array_key_exists($objId, $this->_fileNameProcessors)) {
                return $this->_fileNameProcessors[$objId];
            }
        }
        return false;
    }
    
    public function findByOriginalRelativeFilePath($originalRelativeFilePath)
    {
        if (array_key_exists($originalRelativeFilePath, $this->_fileNameByOriginalRelativeFilePathIndex)) {
            $objId = $this->_fileNameByOriginalRelativeFilePathIndex[$originalRelativeFilePath];
            if ($objId && array_key_exists($objId, $this->_fileNameProcessors)) {
                return $this->_fileNameProcessors[$objId];
            }
        }
        return false;
    }
    
    public function findByOriginalClassName($originalClassName)
    {
        if (array_key_exists($originalClassName, $this->_fileNameByOriginalClassNameIndex)) {
            $objId = $this->_fileNameByOriginalClassNameIndex[$originalClassName];
            if ($objId && array_key_exists($objId, $this->_fileNameProcessors)) {
                return $this->_fileNameProcessors[$objId];
            }
        }
        return false;
    }
    
    public function findByNewFullyQualifiedName($newFullyQualifiedName)
    {
        if (array_key_exists($newFullyQualifiedName, $this->_fileNameByNewFullyQualifiedNameIndex)) {
            $objId = $this->_fileNameByNewFullyQualifiedNameIndex[$newFullyQualifiedName];
            if ($objId && array_key_exists($objId, $this->_fileNameProcessors)) {
                return $this->_fileNameProcessors[$objId];
            }
        }
        return false;
    }
    
    public function getFileNameProcessorForContentProcessor(FileContentProcessor $fileContentProcessor)
    {
        $fcpObjectId = spl_object_hash($fileContentProcessor);

        $objectId = $this->_fileContentProcessorToFileNameProcessorIndex[$fcpObjectId];
        return $this->_fileNameProcessors[$objectId];
    }
    
    public function setIterationType($iterationType = self::ITERATE_NAMES)
    {
        $this->_iterationType = $iterationType;
    }
    
    public function getIterator()
    {
        switch ($this->_iterationType) {
            case self::ITERATE_CONTENTS:
                return new \ArrayIterator($this->_fileContentProcessors);
            case self::ITERATE_NAMES:
            default:
                return new \ArrayIterator($this->_fileNameConverters);
        }
    }
    
    public function count()
    {
        return count($this->_fileNameProcessors);
    }
    
}
