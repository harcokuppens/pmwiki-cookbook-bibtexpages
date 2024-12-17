<?php if (!defined('PmWiki')) exit();

# Recipe version (date).
$RecipeInfo['Bibtex']['Version'] = '2023-08-23';

#-----------------------------------------------
#  Edit bibtex using pmwiki    
#-----------------------------------------------

# config
#--------

# require utf8 in wiki pages, because bibtex files can be utf8 and are imported as utf8 wikipages.
# IMPORTANT: first enable utf8 in pmwiki see: https://www.pmwiki.org/wiki/PmWiki/UTF-8
include_once("scripts/xlpage-utf-8.php");
#$DefaultPageCharset = array(''=>'ISO-8859-1');

# the bibtex cookbook has a data folder for generated bibtex source and html file and for git repo
# store cookbook's data in wiki.d subfolder which is convenient because webserver has write rights there,
# and direct web access is disabled with .htaccess file for wiki.d/ folder.
SDV($BibtexDataDir, "$FarmD/wiki.d/bibtexpages/");
SDVA($BibtexConfig, [
  'enablePublicationsPage'  => true,
  'publications_source'     => "$BibtexDataDir/publications.bib",  # store publication source 
  'publications_html'       => "$BibtexDataDir/publications.html", # and html

  'enableGitBackup'         => false,  # on each edit of Bibtex wiki page a backup copy is saved in the git repo and pushed to remote.
  'gitrepo'                 => "your-repo-url",   # url location of git repo using SSH  (git@GITSERVER:REPOPATH) 
  'gitdir'                  => "$BibtexDataDir/bibtexrepo/",  # directory where git repo locally gets cloned
  'git_deploy_key'          => "$BibtexDataDir/.ssh/id",  # ssh deploy key specific for repo used to authorize to the git repo
  'emaildomain'             => "unknown.com",     # user name ($AuthId)  and email ($AuthId@$emaildomain)  used in git commit      

  'initFromGit'             => false,  # SAFE: preserves existing pages; initialization from git only happens if no bibtex wiki pages are found
  'initFromExampleData'     => true,  # SAFE: preserves existing pages; initialization from example data only happens if no bibtex wiki pages are found
  # note: initFromGit is used if both are true 
  # note: to use one of the init options above you have to delete all bibtex data pages from wiki.d/ folder 
  #       either with command rm wiki.d/Bibtex.*  or manually using the wiki


  'force_generate'          => false, # can be set to true temporary to force generation of a new publications.bib and publications.html from bibtex pages
]);
# note: We use a repository specific ssh deploy key which in gitlab has no expiration date. 
#       We don not use a project access token specific for the repository because this token has a maximum expiration date of 1 year.


$pagename = MakePageName($DefaultPage, $pagename);


// Add a custom wikipage storage location for bundles pages.
// idea from https://www.pmwiki.org/wiki/Cookbook/ModuleGuidelines
global $WikiLibDirs;
array_push($WikiLibDirs, new PageStore("$FarmD" . '/cookbook/bibtexpages/wikilib.d/{$FullName}'));


# bibtex page filter 
#--------------------

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



# enable 
#  1. show history of bibtex changes 
#  2. view bibtex formatted as html or as source with syntax highlighting , 
#  3. editing bibtex in pmwiki with validation, preview and syntax highlighting      
#----------------------------------------------------------------------------------

if (isBibtexDataPage($pagename)) {

  require_once("$FarmD/cookbook/bibtexpages/bibtexutils.php");

  # enable syntax highlighting for Bibtex
  if ($action == "source"  || $action == "edit" || ($action == "browse" && $pagename == "Bibtex.Definitions")) {
    require_once("$FarmD/cookbook/bibtexpages/syntaxhighlighting.php");
    enableSyntaxHighlightingForBibtex();
  }

  # when showing history of bibtex (diff action)
  # --------------------
  #  -  we do not show option to show diff in formatted markup, because bibtex source is not standard pmwiki markup source
  # see: scripts/pagerev.php
  SDV($PageDiffFmt, "<h2 class='wikiaction'>$[{\$FullName} History]</h2> <p>$DiffMinorFmt </p> ");

  # when viewing bibtex page (bibtex formatted as html) (browse action)
  # -----------------------
  #   - display with bibber formatted page of the bibtex source
  #   - display formatted source of Bibtex.Definitions page 
  if ($pagename == "Bibtex.Definitions") {
    $HandleActions["browse"] = "HandleBibtexSourceInSkin";
  } else {
    $HandleActions["browse"] = "HandleBibtexFormattedInSkin";
  }

  # when viewing source of bibtex (source action)
  # -------------------
  #  - display bibtex source formatted
  $HandleActions["source"] = "HandleBibtexSourceInSkin";
  $HandleActions["rawsource"] = "HandleBibtexRawSource";

  # when editing (edit action): 
  # -------------
  #   - validate whether edited source is correct. 
  #      -> If not the save is rejected with error message.
  #      -> else (valid source) then standard pmwiki code saves the new text into pmwiki page
  #   - replace the standard preview function with our special variant for Bibtex
  include_once("$FarmD/scripts/simuledit.php");
  array_unshift($EditFunctions, 'ValidateSource');
  $EditFunctions = str_replace("PreviewPage", "PreviewPageBibtex", $EditFunctions);
}

# function to abort after which current page is shown with error at top in the view
#------------------------------------------------------------------------------------------

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

# create BibtexDataDir if  needed and not yet exist
if ($BibtexConfig['enableGitBackup'] || $BibtexConfig['initFromGit'] || $BibtexConfig['enablePublicationsPage']) {
  if (! file_exists("$BibtexDataDir")) {
    $returnval = mkdir("$BibtexDataDir");
    if ($returnval == false) ErrorAbort($pagename, "problem in creating bibtexpages data directory", -1, false);
  }
}

if ($BibtexConfig['enableGitBackup'] || $BibtexConfig['initFromGit']) require_once("$FarmD/cookbook/bibtexpages/git.php");

# initialize bibtex wiki pages from bibtex git repository (only when no existing bibtex pages are found!)
# -------------------------------------------------------


if ($BibtexConfig['initFromGit'] || $BibtexConfig['initFromExampleData']) {

  # only init from git when no existing bibtex pages are found!
  $bibfiles = array_merge(glob("$FarmD/wiki.d/Bibtex.[12]*"), glob("$FarmD/wiki.d/Bibtex.Definitions"));
  if (!$bibfiles) {
    # import .bib files from repo as pages in wiki in Bibtex group
    require_once("$FarmD/cookbook/bibtexpages/importwikipagefromfile.php");
    if ($BibtexConfig['initFromGit']) {
      bibtex_importfiles($gitdir);
    } else {
      bibtex_importfiles("$FarmD/cookbook/bibtexpages/exampledata/");
    }
    $BibtexConfig['force_generate'] = true;
  }
}




if ($BibtexConfig['enablePublicationsPage']) require_once("$FarmD/cookbook/bibtexpages/publicationspage.php");
