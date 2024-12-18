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
  'enable_publications_page' => true,
  'publications_dir'         => "$BibtexDataDir",
  'publications_source'      => "publications.bib",  # store publication source 
  'publications_html'        => "publications.html", # and html
  'publications_link_html'   => true,  # whether publications directives shows on top a link to raw html   
  'publications_link_source' => true,  # whether publications directives shows on top a link to raw bibtex source  

  # editing bibtex is different from editing normal wiki pages, therefore we give options to show an action menu
  # specially for bibtex own action menu; 
  'show_bibtex_action_menu'  => true,

  'enable_git_backup'        => false,  # on each edit of Bibtex wiki page a backup copy is saved in the git repo and pushed to remote.
  'git_repo'                 => "your-repo-url",   # url location of git repo using SSH  (git@GITSERVER:REPOPATH) 
  'git_dir'                  => "$BibtexDataDir/bibtexrepo/",  # directory where git repo locally gets cloned
  'ssh_config_dir'           => "$BibtexDataDir/.ssh/", # directory where ssh keys are stored 
  'git_deploy_key'           => "id",  # ssh deploy key specific for repo used to authorize to the git repo
  'emaildomain'              => "unknown.com",     # user name ($AuthId)  and email ($AuthId@$emaildomain)  used in git commit      

  'init_from_git'             => false,  # SAFE: preserves existing pages; initialization from git only happens if no bibtex wiki pages are found
  'init_from_example_data'     => true,  # SAFE: preserves existing pages; initialization from example data only happens if no bibtex wiki pages are found
  # note: init_from_git is used if both are true 
  # note: to use one of the init options above you have to delete all bibtex data pages from wiki.d/ folder 
  #       either with command rm wiki.d/Bibtex.*  or manually using the wiki


  'force_generate'          => false, # can be set to true (temporary) to force regeneration of new publications.bib and publications.html files from the bibtex pages
]);
# note: We use a repository specific ssh deploy key which in gitlab has no expiration date. 
#       We don not use a project access token specific for the repository because this token 
#       has a maximum expiration date of 1 year.


// add configuration of a custom wikipage storage location for  pages bundled in cookbook folder.
// idea from https://www.pmwiki.org/wiki/Cookbook/ModuleGuidelines
global $WikiLibDirs;
array_push($WikiLibDirs, new PageStore("$FarmD" . '/cookbook/bibtexpages/wikilib.d/{$FullName}'));

# load generic utility functions needed for cookbook
require_once("$FarmD/cookbook/bibtexpages/utils.php");

# initialize 
# ------------

# initialize stuff only on first request; later requests  

# create BibtexDataDir if  needed and not yet exist
if ($BibtexConfig['enable_git_backup'] || $BibtexConfig['init_from_git'] || $BibtexConfig['enable_publications_page']) {
  if (! file_exists("$BibtexDataDir")) {
    $returnval = mkdir("$BibtexDataDir");
    if ($returnval == false) ErrorAbort($pagename, "problem in creating bibtexpages data directory", -1, false);
  }
}

# initialize git repository if needed
if ($BibtexConfig['enable_git_backup'] || $BibtexConfig['init_from_git']) require_once("$FarmD/cookbook/bibtexpages/git.php");

# initialize bibtex wiki pages from bibtex git repository or example pages (only when no existing bibtex pages are found!)
if ($BibtexConfig['init_from_git'] || $BibtexConfig['init_from_example_data']) {

  # only init from git when no existing bibtex pages are found!
  $bibfiles = array_merge(glob("$FarmD/wiki.d/Bibtex.[12]*"), glob("$FarmD/wiki.d/Bibtex.Definitions"));
  if (!$bibfiles) {
    # import .bib files from repo as pages in wiki in Bibtex group
    require_once("$FarmD/cookbook/bibtexpages/importwikipagefromfile.php");
    if ($BibtexConfig['init_from_git']) {
      bibtex_importfiles($BibtexConfig['git_dir']);
    } else {
      bibtex_importfiles("$FarmD/cookbook/bibtexpages/exampledata/");
    }
    $BibtexConfig['force_generate'] = true;
  }
}


# deal bibtex pages specially
#-----------------------------

# enable for bibtex data page only
#  1. show history of bibtex changes 
#  2. view bibtex formatted as html or as source with syntax highlighting , 
#  3. editing bibtex in pmwiki with validation, preview and syntax highlighting      

$pagename = MakePageName($DefaultPage, $pagename);
if (isBibtexDataPage($pagename)) {


  # include functions specific for bibtex data pages
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
  $HandleActions["rawhtml"] = "HandleBibtexRawHtml";

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


# create static publications html and source pages after every bibtex edit to allow fast loading 
# -----------------------------------------------------------------------------------------------
if ($BibtexConfig['enable_publications_page']) require_once("$FarmD/cookbook/bibtexpages/publicationspage.php");
