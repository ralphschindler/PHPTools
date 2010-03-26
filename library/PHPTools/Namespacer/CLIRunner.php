<?php

namespace PHPTools\Namespacer;

class CLIRunner
{

    public static function main()
    {
        $cliRunner = new self();
        $cliRunner->run();
    }
    
    public function __construct()
    {
        if (PHP_SAPI != 'cli') {
            throw new \RuntimeException('This class is only available in the CLI PHP Environment');
        }
    }
    
    public function run($options = array())
    {
        $namespacer = new Namespacer();
        $this->_parseOptions($namespacer, $options);
    }
    
    protected function _parseOptions(Namespacer $namespacer, array $options = array())
    {
        if (!$options) {
        
            $usersOptions = getopt(
                'h::l::d::o::p::s::m::',
                array(
                    'help::',
                    'lib::',
                    'library-directory::',
                    'dir::',
                    'directory-filter::',
                    'out::',
                    'output-path::',
                    'prefix::',
                    'prefixes::',
                    'stats::',
                    'show-statistics::',
                    'map::',
                    'map-path::'
                    )
                );
    
            $userToOfficialNames = array(
                'h' => 'help',
                'help' => 'help',
                'l' => 'libraryDirectory',
                'lib' => 'libraryDirectory', 
                'library-directory' => 'libraryDirectory',
                'd' => 'directoryFilter',
                'dir' => 'directoryFilter',
                'directory-filter' => 'directoryFilter',
                'o' => 'outputPath',
                'out' => 'outputPath',
                'output-path' => 'outputPath',
                'p' => 'prefixes',
                'prefix' => 'prefixes',
                'prefixes' => 'prefixes',
                's' => 'showStatistics',
                'stats' => 'showStatistics',
                'show-statistics' => 'showStatistics',
                'm' => 'mapPath',
                'map' => 'mapPath',
                'map-path' => 'mapPath'
                );
            
            $options = array();
            
            foreach ($userToOfficialNames as $userOptionName => $officialName) {
                if (isset($usersOptions[$userOptionName])) {
                    $options[$officialName] = $usersOptions[$userOptionName];
                }
            }
        }
        
        if (isset($options['help'])) {
            $this->_showHelp();
            return;
        }

        try {
            $namespacer->setOptions($options);
            $namespacer->convert();
        } catch (\Exception $e) {
            echo 'Exception caught ' . get_class($e) . ' : ' . $e->getMessage();
            exit(1);
        }

    }
    
    protected function _showHelp()
    {
        echo <<<EOS
This tool is intended to be used to namespace previously prefixed library 
code developed with a PEAR/ZF coding standard in place.  It will attempt
to find all class names and convert them to namespaces.  Furthermore, it
will attempt to find any references to those classes in method signatures,
and body code, and docblocks and convert those to known translations.

Usage:
    (Option should be passed in a form that php's getopt() can parse.)
    php path/to/Namespace/Namespacer.php [options]

Options:
    -h, --help
        This help screen.
    -l, --lib, --library-directory
        The library directory to iterate, this would be the same directory
        you would anticipate registered as an include_path.
    -d, --dir, --directory-filter
        The part of the library directory you want to operate on.
    -o, --out, --output-path
        If supplied, this directory will be where converted files are
        written to.
    -p, --prefix, --prefixes
        The base prefix to mind when converting.  Can be comma separated
        list.
    -m, --map, --map-path
        The directory where an xml file will be produced that will list
        the file and class translations that were used.
        
Notes:
    * library and directory are separate entities b/c library will be
      first scanned to identify all names in general usage. Directory
      will be used to filter out the relative path that is to be
      considered the working set of file that the converter should
      convert.


EOS;
    }
    
}

