<?php if (!defined('PmWiki')) exit();


# publications overview page
#=============================

# In the wiki we have multiple bibtex pages from which this code makes:
#  - a single .bib  file
#  - a single .html file  (nice view generated from source)
# On each save of a bibtex wiki page above files are updated.
#
# These files can be show in any wiki page in the wiki by using the directive:
#
#   (:includepublications:)     :  includes html to view all publications
#                                  and outputs link to bibtex source.
#
# Example usage of directive in example wikipage 'Publications':
#  
#    !Publications
#  
#    You may download the list of publications on this page as [[ {$Name}?rawsrcpublications | Bibtex source ]]. \\
#    Members of our group can add new bibtex entries in the Bibtex pages in the [[Bibtex/|  Bibtex group ]].
#  
#    (:includepublications:)


#-----------------------------------------------------------------------------------------------
#   setup
#-----------------------------------------------------------------------------------------------


# only generate the publications page (html and source page) when you succesfully saved a bibtex data page
#-------------------------------------------------------------------------------------------------------

if (isBibtexDataPage($pagename) && $action == "edit"   &&   $EnablePost == true) {
  $EditFunctions[] = "generatePublicationsPage"; // Add to end of $EditFunctions
}

# generate initial page directly on first request
#------------------------------------------------

# if not yet created then create publications page on first query to a page on the wiki
$publications_html = $BibtexConfig['publications_html'];
if (! file_exists($publications_html) || $BibtexConfig['force_generate']) {
  require_once("$FarmD/cookbook/bibtexpages/bibtexutils.php");
  $xpage = null;
  $new = null;
  generatePublicationsPage($pagename, $xpage, $new);
}

# includepublications directive
#-------------------------------
# define a directive you can place in a wiki page to include the publications overview page in that wiki page

Markup('includepublications', 'directives', '/\\(:includepublications\s*:\\)/', "mu_include_pub_mixed");


#-----------------------------------------------------------------------------------------------
#   functions
#-----------------------------------------------------------------------------------------------


# generate a single publications overview page and source page from all bibtex sources combined
#-----------------------------------------------------------------------------------------------
function generatePublicationsPage($pagename, &$page, &$new)
{
  global $EnablePost, $FarmD, $BibtexConfig;
  $publications_html = $BibtexConfig['publications_html'];
  $publications_source = $BibtexConfig['publications_source'];
  if (file_exists($publications_html) && $EnablePost != true) {
    // if a publications page does not yet exist then we do not skip and generate one. 
    // otherwise we skip generating publications page because no new wiki page is saved in a POST request
    return false;
  }

  # store latest years direct after definitions in source output file
  $data = array();
  $text = ReadPage("Bibtex.Definitions", READPAGE_CURRENT)['text'];
  if ($text)  $data[] = $text;
  $bibfiles = glob("$FarmD/wiki.d/Bibtex.[12]*");
  rsort($bibfiles);
  foreach ($bibfiles as $bibfile) {
    $text = ReadPage(basename($bibfile), READPAGE_CURRENT)['text'];
    if ($text)  $data[] = $text;
  }

  # note: initially no "Bibtex.Definitions" and Bibtex.YEAR files are setup, so no data to write
  if ($data) {
    # save bibtex source to file
    $returnval = file_put_contents($publications_source, implode("\n\n", $data));
    if ($returnval == false) ErrorAbort($pagename, "saving publications bibtex source failed", -1, false);
    # process bibtex source to html  
    $outputFormat = "htmlsectioned";
    $BibtexData = run_bibber(implode("\n\n", $data), $outputFormat);
    $exitCode = $BibtexData['exitCode'];
    if ($exitCode != 0) {
      $errormsg = $BibtexData['errorText'];
      //$inline = file_exists($publications_html);
      $inline = false;
      ErrorAbort($pagename, "problem generating overview page for publications<br>$errormsg", $exitCode, $inline);
    }
    $html = $BibtexData['output'];
  } else {
    $html = "no publications yet!";
  }

  $returnval = file_put_contents($publications_html, $html);
  if ($returnval == false) ErrorAbort($pagename, "saving publications html failed", -1, false);
}





# include the publications overview page in wiki page
#----------------------------------------------------
function mu_include_pub_embedhtml($m)
{
  global $BibtexConfig;

  $publications_html = $BibtexConfig['publications_html'];
  $links = "";
  if ($BibtexConfig['publications_link_source']) {
    $links = "<a href='?rawsrcpublications'>source</a>";
  }
  if ($BibtexConfig['publications_link_html']) {
    $links = $links . " <a href='?rawhtmlpublications'>html</a>";
  }
  $text = $links . "<br>" . file_get_contents($publications_html);
  # We use the Keep() function here to prevent the output from being further processed by PmWiki's markup rule
  return Keep($text);
}

# note: advised not used, because to slow for large source files
#        -> that's why in mu_include_pub_embedhtml I disabled link to ?srcpublications in favor of ?rawsrcpublications
function mu_include_pub_embedsrc($m)
{
  global $FarmD, $BibtexConfig;

  $publications_source = $BibtexConfig['publications_source'];
  $bibtexSource = file_get_contents($publications_source);
  if (trim($bibtexSource) == '') {
    $html = 'Page contains no text';
  } else {
    require_once("$FarmD/cookbook/bibtexpages/syntaxhighlighting.php");
    enableSyntaxHighlightingForBibtex();
    # use prismjs for syntax highlighting source code ; basic usage see https://prismjs.com/#basic-usage
    $html = "<pre id='bibtex'  class='linkable-line-numbers line-numbers'><code class='language-bibtex match-braces'>" . htmlspecialchars($bibtexSource, ENT_QUOTES) . "</code></pre>";
  }

  // $links= "<a href='?'>view formatted</a><br>";
  // $html = "<h2 class='wikiaction'>Source Publications </h2><br>$links" . $html;

  $links = "<a href='?rawsrcpublications'>raw source</a><br>";
  $html = "<h2 class='wikiaction'>Source Publications </h2>$links<br>" . $html;

  # We use the Keep() function here to prevent the output from being further processed by PmWiki's markup rule
  return Keep($html);
}

function mu_include_pub_rawsource($m)
{
  global $HTTPHeaders, $BibtexConfig;

  $publications_source = $BibtexConfig['publications_source'];

  $bibtexSource = file_get_contents($publications_source);
  foreach ($HTTPHeaders as $h) {
    $h = preg_replace('!^Content-type:\\s+text/html!i', 'Content-type: text/plain', $h);
    header($h);
  }
  echo $bibtexSource;
  exit;
}

function mu_include_pub_rawhtml($m)
{
  global $HTTPHeaders, $BibtexConfig;

  $publications_html = $BibtexConfig['publications_html'];

  $htmlSource = file_get_contents($publications_html);
  foreach ($HTTPHeaders as $h) {
    #$h = preg_replace('!^Content-type:\\s+text/html!i', 'Content-type: text/plain', $h);
    header($h);
  }
  echo $htmlSource;
  exit;
}

function mu_include_pub_mixed($m)
{
  if (strpos($_SERVER['QUERY_STRING'], "rawsrcpublications") !== false) {
    return mu_include_pub_rawsource($m);
  } elseif (strpos($_SERVER['QUERY_STRING'], "srcpublications") !== false) {
    return mu_include_pub_embedsrc($m);
  } elseif (strpos($_SERVER['QUERY_STRING'], "rawhtmlpublications") !== false) {
    return mu_include_pub_rawhtml($m);
  } else {
    return mu_include_pub_embedhtml($m);
  }
}
