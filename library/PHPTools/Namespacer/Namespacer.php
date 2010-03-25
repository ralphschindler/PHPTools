<?php

namespace PHPTools\Namespacer;

class Namespacer
{
    
    protected $_libraryDirectory = null;
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
                $this->_libraryDirectory = realpath($optionValue);
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
            
            if ($this->_mapPath) {
                $xmlWriter = new \XMLWriter();
                $xmlWriter->openURI($this->_mapPath . '/PHPNamespacer-MappedClasses.xml');
                $xmlWriter->setIndent(true);
                $xmlWriter->setIndentString('   ');
                $xmlWriter->startDocument('1.0');
                $xmlWriter->startElement('MappedClasses');
                $xmlWriter->writeAttribute('libraryPath', $this->_libraryDirectory);
            }
            
            foreach (new \RecursiveIteratorIterator($rdi) as $realFilePath => $fileInfo) {
                $relativeFilePath = substr($realFilePath, strlen($this->_libraryDirectory)+1);
                $fileNameProcessor = new FileNameProcessor($relativeFilePath, $this->_libraryDirectory);
                // add only classes that contain a matching prefix
                if (!$this->_prefixes || preg_match('#^' . implode('|', $this->_prefixes) . '#', $fileNameProcessor->getOriginalClassName())) {
                    $this->_fileRegistry->registerFileNameProcessor($fileNameProcessor);
                    if (isset($xmlWriter)) {
                        $xmlWriter->startElement('MappedClass');
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
            
            foreach (new \RecursiveIteratorIterator($it) as $realFilePath => $fileinfo) {
                if ($fileinfo->isFile()) {
                    $fileNameProc = $this->_fileRegistry->findByOriginalFilePath($realFilePath);
                    if ($fileNameProc) {
                        $fileContentProcessor = new FileContentProcessor($fileNameProc, $this->_prefixes, $this->_fileRegistry);
                        $this->_fileRegistry->registerFileContentProcessor($fileContentProcessor);
                    }
                }
            }
            
            if (isset($xmlWriter)) {
                $xmlWriter->endElement();
                $xmlWriter->endDocument();
                $xmlWriter->flush();
            }
            
            if ($this->_outputPath) {

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
