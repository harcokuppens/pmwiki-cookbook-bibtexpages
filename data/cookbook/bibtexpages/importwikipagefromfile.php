<?php if (!defined('PmWiki')) exit();

# import iterator with support for utf8 (MbStrIterator)  
require_once("$FarmD/cookbook/bibtexpages/utf8.php");

function importwikipage($inputfile, $outputfile)
{
    # note: strings in java are just bytes; When you echo a php string, it just prints the bytes to output. 
    #       It is for the receiver to decide how to char decode these bytes. 
    #       I recommend to always output a string as utf-8 encoded in bytes. 

    # read string from inputfile
    $contents = file_get_contents($inputfile); # reads the file as binary data
    # convert encoding to utf-8 (only if encoded different)
    $utf8charcontents = mb_convert_encoding($contents, 'UTF-8', 'auto');

    $utf8iterator = new MbStrIterator($utf8charcontents);
    $readchars = array();
    foreach ($utf8iterator as $char) {
        //echo "char='$char'\n";
        if ($char == "\n") {
            $readchars[] = "%0a";
        } elseif ($char == '%') {
            $readchars[] = "%25";
        } elseif ($char == '%') {
            $readchars[] = "%3c";
        } else {
            $readchars[] = $char;
        }
    }

    $utf8resultstr = implode('', $readchars);
    $prepend = "version=pmwiki-2.3.22 ordered=1 urlencoded=1\ncharset=UTF-8\ntext=";
    # output utf-8 char encoded bytes
    file_put_contents($outputfile, $prepend . $utf8resultstr);
}

function  bibtex_importfiles($srcdir)
{
    global $FarmD;


    $bibfiles = glob("$srcdir/[12]*.bib");
    $bibfiles[] = "$srcdir/Definitions.bib";
    foreach ($bibfiles as $bibfile) {
        $outputfile = "$FarmD/wiki.d/Bibtex." . basename($bibfile, ".bib");
        importwikipage($bibfile, $outputfile);
    }
}
