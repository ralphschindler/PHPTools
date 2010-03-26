<?php

namespace PHPTools\Namespacer;
use XMLReader, XMLWriter;

class Namespacer
{
    
    protected $_libraryDirectory = null;
    protected $_libraryDirectoryOriginal = null;
    protected $_filePath = null;
    protected $_directoryFilter = null;
    protected $_outputPath = null;
    protected $_mapPath = null;
    protected $_prefixes = array();
    protected $_showStatistics = false;
    protected $_fileRegistry = null;
    
    public function __construct($options = array())
    {
        if ($options) {
            $this->setOptions($options);
        }
        $this->_fileRegistry = new FileRegistry();
    }
    
    public function setOptions(Array $options)
    {
        foreach ($options as $optionName => $optionValue) {
            $this->setOption($optionName, $optionValue);
        }
    }
    
    public function setOption($optionName, $optionValue = true)
    {
        switch ($optionName) {
            case 'libraryDirectory':
                $this->_libraryDirectoryOriginal = $optionValue;
                $this->_libraryDirectory = realpath($this->_libraryDirectoryOriginal);
                if (!file_exists($this->_libraryDirectory)) {
                    throw new \InvalidArgumentException('Library directory provided does not exist (' . $this->_libraryDirectory . ')');
                }
                break;
            case 'directoryFilter':
                $this->_directoryFilter = $optionValue;
                break;
            case 'outputPath':
                $this->_outputPath = $optionValue;
                if (!file_exists(realpath($this->_outputPath))) {
                    if (!file_exists(realpath(dirname($this->_outputPath)))) {
                        throw new \InvalidArgumentException('The output path provided does not exist and cannot be created in ' . $this->_outputPath);
                    }
                    mkdir($this->_outputPath);
                }
                break;
            case 'mapPath':
                $this->_mapPath = $optionValue;
                if (!file_exists(realpath($this->_mapPath))) {
                    if (!file_exists(realpath(dirname($this->_mapPath)))) {
                        throw new \InvalidArgumentException('The output path provided does not exist and cannot be created in ' . $this->_mapPath);
                    }
                    mkdir($this->_mapPath);
                }
                break;
            case 'prefixes':
                $this->_prefixes = explode(',', $optionValue);
                break;
            case 'showStatistics':
                $this->_showStatistics = $optionValue;
                break;
            default:
                throw new \InvalidArgumentException('Option ' . $optionName . ' is not supporter by ' . __CLASS__);
        }
    }
    
    public function getOptions()
    {
        return array(
            'libraryDirectory' => $this->_libraryDirectory,
            'directoryFilter' => $this->_directoryFilter,
            'outputPath' => $this->_outputPath,
            'prefixes' => $this->_prefixes,
            'showStatistics' => $this->_showStatistics
            );
    }
    
    public function getFileRegistry()
    {
        return $this->_fileRegistry;
    }
    
    public function convert()
    {
        if (isset($this->_libraryDirectory)) {
            $rdi = $it = new \RecursiveDirectoryIterator($this->_libraryDirectory);
            
            if (isset($this->_directoryFilter)) {
                // use our RecursiveFilterIterator, not SPL's
                $it = new RecursiveFilterIterator($rdi, $this->_directoryFilter);
            }
            
            $buildFileMapFromDirectory = true;
            
            if ($this->_mapPath) {
                $mapFilePath = $this->_mapPath . '/PHPNamespacer-MappedClasses.xml';
                $mapFileRealPath = realpath($mapFilePath);
                if (file_exists($mapFileRealPath)) {
                    $this->_loadMapFile($mapFileRealPath);
                    $buildFileMapFromDirectory = false;
                } else {
                    $xmlWriter = new XMLWriter();
                    $xmlWriter->openURI($mapFilePath);
                    $xmlWriter->setIndent(true);
                    $xmlWriter->setIndentString('   ');
                    $xmlWriter->startDocument('1.0');
                    $xmlWriter->startElement('mappedClasses');
                    $xmlWriter->writeAttribute('libraryDirectory', $this->_libraryDirectoryOriginal);
                }
            }
            
            if ($buildFileMapFromDirectory) {
                foreach (new \RecursiveIteratorIterator($rdi, \RecursiveIteratorIterator::SELF_FIRST) as $realFilePath => $fileInfo) {
                    $relativeFilePath = substr($realFilePath, strlen($this->_libraryDirectory)+1);
                    if (preg_match('#(\.svn|_svn|\.git)#', $relativeFilePath) || !preg_match('#\.php$#', $relativeFilePath)) {
                        continue;
                    }
                    $fileNameProcessor = new FileNameProcessor($relativeFilePath, $this->_libraryDirectory);
                    // add only classes that contain a matching prefix
                    if (!$this->_prefixes || preg_match('#^' . implode('|', $this->_prefixes) . '#', $fileNameProcessor->getOriginalClassName())) {
                        $this->_fileRegistry->registerFileNameProcessor($fileNameProcessor);
                        if (isset($xmlWriter)) {
                            $xmlWriter->startElement('mappedClass');
                            $xmlWriter->writeElement('originalRelativeFilePath', $fileNameProcessor->getOriginalRelativeFilePath());
                            $xmlWriter->writeElement('originalClassName', $fileNameProcessor->getOriginalClassName());
                            $xmlWriter->writeElement('newRelativeFilePath', $fileNameProcessor->getNewRelativeFilePath());
                            $xmlWriter->writeElement('newNamespace', $fileNameProcessor->getNewNamespace());
                            $xmlWriter->writeElement('newClassName', $fileNameProcessor->getNewClassName());
                            $xmlWriter->writeElement('newFullyQualifiedName', $fileNameProcessor->getNewFullyQualifiedName());
                            $xmlWriter->endElement();
                        }
                    }
                }
            }
            
            if (isset($xmlWriter)) {
                $xmlWriter->endElement();
                $xmlWriter->endDocument();
                $xmlWriter->flush();
                echo 'Number of classes written to map file: ' . count($this->_fileRegistry) . PHP_EOL;
            }
            
            if ($this->_outputPath) {

                foreach (new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::SELF_FIRST) as $realFilePath => $fileInfo) {
                    if ($fileInfo->isFile()) {
                        $relativeFilePath = substr($realFilePath, strlen($this->_libraryDirectory)+1);
                        $fileNameProc = $this->_fileRegistry->findByOriginalRelativeFilePath($relativeFilePath);
                        if ($fileNameProc) {
                            $fileContentProcessor = new FileContentProcessor($fileNameProc, $this->_prefixes, $this->_fileRegistry);
                            $this->_fileRegistry->registerFileContentProcessor($fileContentProcessor);
                        }
                    }
                }
                
                $this->_fileRegistry->setIterationType(FileRegistry::ITERATE_CONTENTS);
                foreach ($this->_fileRegistry as $fileContentProc) {
                    $fileNameProc = $this->_fileRegistry->getFileNameProcessorForContentProcessor($fileContentProc);
                    
                    $base = dirname($fileNameProc->getNewRelativeFilePath());
                    if (!file_exists($this->_outputPath . '/' . $base)) {
                        mkdir($this->_outputPath . '/' . $base, 0777, true);
                    }
                    
                    file_put_contents($this->_outputPath . '/' . $fileNameProc->getNewRelativeFilePath(), $fileContentProc->getNewContents());
                }
            }
        } else {
            throw new \RuntimeException('Neither a filePath or a libraryDirectory was supplied to the Namespacer.');
        }

    }

    protected function _loadMapFile($mapFile)
    {
        echo 'Loading a map file found at ' . $mapFile . PHP_EOL;
        $mappedClassElementNames = array(
            'originalRelativeFilePath',
            'originalClassName',
            'newRelativeFilePath',
            'newNamespace',
            'newClassName',
            'newFullyQualifiedName',
            );
        $reader = new XMLReader();
        $reader->open($mapFile);

        $map = array();
        while ($reader->read()) {
            if ($reader->name == 'mappedClasses' && $reader->nodeType == XMLReader::ELEMENT) {
                $libraryDirectory = $reader->getAttribute('libraryDirectory');
                $realLibraryDirectory = realpath($libraryDirectory);
                if ($realLibraryDirectory != $this->_libraryDirectory) {
                    throw new \UnexpectedValueException('The libraryDirectory located in the map file is not the same as the one provided for execution.');
                }
                continue;
            }
            if ($reader->name == 'mappedClass' && $reader->nodeType == XMLReader::ELEMENT) {
                $mappedClass = array();
                
                foreach ($reader->expand()->childNodes as $domNode) {
                    if (in_array($domNode->nodeName, $mappedClassElementNames)) {
                        $mappedClass[$domNode->nodeName] = $domNode->nodeValue;
                    }
                }

                $fileNameProcessor = new FileNameProcessor($mappedClass, $libraryDirectory);
                $this->_fileRegistry->registerFileNameProcessor($fileNameProcessor);
                $reader->next();
            }
        }
        
    }
    
    protected function _displayInfo($infoArray)
    {
        echo '  Class name found: ' . $infoArray['className'] . PHP_EOL;
        if (isset($infoArray['consumedClasses'])) {
            echo '  Classes consumed:' . PHP_EOL;
            foreach ($infoArray['consumedClasses'] as $consumedClass) {
                echo '       ' . $consumedClass . PHP_EOL;
            }
        }
        echo PHP_EOL;
    }

}
