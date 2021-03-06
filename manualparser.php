<?php
/**
*    PHP.net DocBook manual parser.
*   
*    The purpose of the class is to generate a XML file that
*    contains all the function calls available in the PHP manual,
*    this will be used to make a CLI function lookup program.
*
*    @package Epc_Xml
*    @copyright 2004 Havard Eide
*/

// {{{ class EPCManualParser
class EPC_XML_ManualParser
{
    // {{{ private variables
    private $settings;
    private $buffer;
    // }}}

    // {{{ public function __construct( $startDir )
    /**
     *    Create a new manual parser
     *
     *    @param $startDir string Where are we starting the parsing
     *    @access public
     *    @return void
     */
    public function __construct( $conf )
    {
        if( file_exists( $conf ) )
        {
            $this->settings = simplexml_load_file( $conf );
            if( !file_exists( $this->settings->cvsdir ) )
            {
                die(
                "Can't find the CVS dir in: " .
                $this->settings->cvsdir
               );
            }
        }
        else
        {
            die( "Can't load a settings file that is missing...." );
        }
        $this->buffer = '';
    }
    // }}}

    // {{{ public function run()
    /**
     *    Main function on the object, initiates the parsing
     *    of the main document tree.
     *
     *    @access public
     *    @return void
     */
    public function run()
    {
        // XML declaration is good to have:
        $this->buffer = '<?xml version="1.0" encoding="iso-8859-1"?>';
        $this->buffer .= "\n<manual>";
        /*
         *    We loop over all the directories in the dir given in the
         *    constructor: each of these will have a directory called
         *    "functions", this is where all the XML files we want
         *    to process are.
         */
        $dir = new RecursiveDirectoryIterator(
                $this->settings->cvsdir
            );
        foreach( new RecursiveIteratorIterator($dir) as $file )
        {
            if( $file->isDir() && $file->getFileName() != ".svn" &&  $file->getFileName() != ".." )
            {
                foreach( new RecursiveDirectoryIterator($file) as $f )
                {
                echo $f->getFileName() . "\n";
                    if( $f->getFileName() == "functions" )
                    {
                        echo ">>Processing: $f\n";
                        $this->processDirectory( new DirectoryIterator($f));
                    }
                }
            }
        }
        $this->buffer .= "\n</manual>";
        file_put_contents( $this->settings->save, $this->buffer );
    }
    // }}}
    
    // {{{ private function processDirectory( $file )
    /**
     *  Helper function that iterates over the XML files in a single
     *    "functions/" directory.
     *  It passes each file to the processFile() function.
     *
     *  @access private
     *  @return void
     *  @param $file object A DirectoryIterator instance
     */
    private function processDirectory( DirectoryIterator $file )
    {
        foreach( $file as $f )
        {
            $this->processFile( $f );
        }
    }
    // }}}
    
    // {{{ private function processFile( $file )
    /**
     *    This function is where all the lookup of the manual entry is.
     *
     *    We are very lazy here: we suppress any errors that the
     *    Dom might throw at us, and we load the XML data as HTML -
     *    we're just interested in a particular node - no need to
     *    have a whole document that is valid, as long as we are able
     *    to get to the "methodsynopsis" node.
     *
     *    @param $file object A DirectoryIterator instance
     *    @access private
     *    @return void
     */
    private function processFile( DirectoryIterator $file )
    {
        $dom = new DomDocument();
        @$dom->loadHTML( file_get_contents( $file->getPathName() ) );
        $xpath = new DomXpath( $dom );
        
        // Get the node where the description of the function call is:
        $result = @$xpath->query( "//methodsynopsis" );
        // Make sure we find a node:
        if( $result->item( 0 ) )
        {
            if( $this->settings->debug )
            {
                echo "\tparsing: " . $file->getFileName() . "\n";
            }
            $xml = simplexml_import_dom( $result->item( 0 ) );
            // Set the "function" attribute
            // this is what we will look for later.
            $xml['function'] = (string)$xml->methodname;
            $this->buffer .= "\n" . $xml->asXml();
        }
    }

}
// }}}
?> 
