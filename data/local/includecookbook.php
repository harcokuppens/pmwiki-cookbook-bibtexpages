<?php


#-----------------------------------------------
# bibtex
#-----------------------------------------------

# enable backing up in a git repo using a ssh deploy key specific for this repository
// $BibtexConfig = array(
//     'enableGitBackup'         => false,
//     'gitrepo'                 => "git@gitlab.science.ru.nl:harcok/test-department-publications.git", # ssh url of git repo
//     'git_deploy_key'          => "$FarmD/.ssh/id_ed25519.gitlab.test-department-publications",        # ssh deploy key specific for repo used to authorize to the git repo
//     'emaildomain'             => "science.ru.nl",   # user name ($AuthId)  and email ($AuthId@$emaildomain)  used for commits
// );

@include_once("$FarmD/cookbook/bibtexpages/bibtexpages.php");

// view/edit bibtex in wiki pages
//require_once("$FarmD/cookbook/bibtex/bibtex.php");
