<?php if (!defined('PmWiki')) exit();


# initialize local git repository for bibtex on the fly
# -------------------------------------------------------
#   when a request for a page in Bibtex group occurs and the gitdir does not yet exist, 
#   then clone the remote git repo locally

function  git_clone_bibtex_repo()
{
  global $pagename, $BibtexConfig;

  # clone repo and import its .bib files as wiki pages 
  $gitdir = $BibtexConfig['gitdir'];
  $git_deploy_key = $BibtexConfig['git_deploy_key'];
  $gitrepo = $BibtexConfig['gitrepo'];

  $git_ssh_command = "GIT_SSH_COMMAND='ssh -i $git_deploy_key -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no' git";
  executeShellCommand($pagename, "git clone", "$git_ssh_command clone $gitrepo $gitdir  2>&1", false);
}

if (!file_exists($BibtexConfig['gitdir'])) {
  git_clone_bibtex_repo();
}



# keep a backup of bibtex history in a git repository
# -------------------------------------------------------
#
# after saving a bibtex wiki page, then also save the bibtex source to a local git repo 
# the  backup works as follows:
#  - initially done once: import bib source from git repo
#      * trigger: open a page in Bibtex group and local git dir does not exist.
#      * do when triggered: 
#         1) clone a local copy of the remote repository at the local git dir location
#         2) import the .bib files from the local clone as wiki pages in the Pmwiki group
#  - after each save of a bibtex wiki page:
#     * save the bibtex source from the wikipage to the corresponding file in the local git repo  
#     * commit the changed file in the local repo
#     * pull from a remote git repo where we ignore all remote changes and always 
#       take our version with: 'git pull -s ours'
#     * push our version to a remote git repo
# note: changes done to repo via other ways then via this pmwiki will stay in the git log but
#       will automatically overriden with changes from this pmwiki. 
#       The git repository works as a mirror of the wiki pages in both content and history!

# only backup page to git when you succesfully saved a bibtex data page
if ($BibtexConfig['enableGitBackup'] && isBibtexDataPage($pagename) && $action == "edit"   &&   $EnablePost == true) {
  $EditFunctions[] = "PostSaveBibtexDoGitBackup"; // Add to end of $EditFunctions
}

function handleExecError($pagename, $descriptionCmd, $outputArray,  $retval, $inline = "true")
{
  $output = "&nbsp;&nbsp;&nbsp;&nbsp;" . implode('<br>&nbsp;&nbsp;&nbsp;&nbsp;', $outputArray);
  #error_log("$descriptionCmd; retval: $retval, output: $output");
  if ($retval != 0) ErrorAbort($pagename, "$descriptionCmd failed<br>$output", $retval, $inline);
}
function executeShellCommand($pagename, $descriptionCmd, $cmd, $inline = "true")
{
  $outputArray = null;
  $retval = null;
  exec($cmd, $outputArray,  $retval);
  handleExecError($pagename, $descriptionCmd, $outputArray,  $retval, $inline);
}

function PostSaveBibtexDoGitBackup($pagename, &$page, &$new)
{
  global $EnablePost, $AuthId, $BibtexConfig;

  if ($EnablePost != true) return false; // skip save to git; page save can be aborted because of error in source.

  $gitdir = $BibtexConfig['gitdir'];
  $emaildomain = $BibtexConfig['emaildomain'];
  $git_deploy_key = $BibtexConfig['git_deploy_key'];
  $data = $new['text'];
  $shortname = PageVar($pagename, '$Name');

  $source_outputfile = $gitdir . $shortname . ".bib";
  $returnval = file_put_contents($source_outputfile, $data);
  if ($returnval == false) ErrorAbort($pagename, "saving bibtex source to file in git repo failed");

  $git_ssh_command = "GIT_SSH_COMMAND='ssh -i $git_deploy_key -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no' git -c user.email=$AuthId@$emaildomain -c user.name=$AuthId -C $gitdir";

  # with right user name ($AuthId)  and email ($AuthId@$emaildomain) do 
  # 1) commit 
  executeShellCommand($pagename, "git commit", "$git_ssh_command commit -m'file $shortname.bib edited by $AuthId' $shortname.bib 2>&1");

  # 2) pull  (should not be a problem with conflicts because website is only one doing commits )
  #    note: we use merge strategy ours which always takes our local version and ignores the remote version
  #          See docs at: https://git-scm.com/docs/merge-strategies#Documentation/merge-strategies.txt-ours-1
  executeShellCommand($pagename, "git pull", "$git_ssh_command pull -s ours 2>&1");

  # 3) push
  executeShellCommand($pagename, "git push", "$git_ssh_command push 2>&1");


  # background info:
  # https://stackoverflow.com/questions/47465841/pull-commit-push-or-commit-pull-push
  #  answer: do commit-pull-push
  # -> I added usage of the  'ours' merge strategy when pulling to overcome problems when somebody pushed to the git repo via an other channel. 
  #    The 'ours' strategy always favors our local copy over the remote copy!

}
