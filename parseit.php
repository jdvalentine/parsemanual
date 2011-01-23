<?php
/**
*    PHP manual parser.
*
*    @copyright 2004 Havard Eide
*/
echo ">> Starting up...\n";

include( "./manualparser.php" );
$parser = new EPC_XML_ManualParser( "settings.xml" );
$parser->run();

echo ">> Finished\n\n";
?> 
