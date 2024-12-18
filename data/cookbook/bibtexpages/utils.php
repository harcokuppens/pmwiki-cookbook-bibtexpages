<?php if (!defined('PmWiki')) exit();

# functions 
#-----------

# check page is a bibtex data page
function isBibtexDataPage($pagename): bool
{
    $query1 = 'Bibtex.19';
    $query2 = 'Bibtex.20';
    if (
        substr($pagename, 0, strlen($query1)) === $query1 ||
        substr($pagename, 0, strlen($query2)) === $query2 ||
        $pagename == "Bibtex.Definitions"
    ) {
        return true;
    } else {
        return false;
    }
}

# function to abort after which current page is shown with error at top in the view
function ErrorAbort($pagename, $errormsg, $errorcode = -1, $inline = true)
{
    $codemsg = "";
    if ($errorcode != -1) $codemsg = "($errorcode)";
    $msg = "<br><p style='color:red'> <b> ERROR$codemsg: $errormsg </b></p>\n";
    if ($inline) {
        HandleDispatch($pagename, 'browse', $msg);
    } else {
        # at some points in code we cannot display error message inline so we just print it, and exit.
        print($msg);
    }
    exit;
}
