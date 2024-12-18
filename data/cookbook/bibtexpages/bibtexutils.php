<?php if (!defined('PmWiki')) exit();

function translateBibtex($input, $outputFormat = "htmlsectioned")
{
  # translation requires page "Bibtex.Definitions"
  if (!PageExists("Bibtex.Definitions")) {
    $result = array(
      'exitCode' => -1,
      'errorText' => 'Cannot translate bibtex, because required page "Bibtex.Definitions" not found.',
      'output' => ''
    );
    return $result;
  }
  # first run on page text only (without definitions), because if an error is found, 
  # its line number then correspondents with the source.
  # note: bibber runs fine without definitions defined; it just puts an undefined definition as lowercase string in the output
  $result = run_bibber($input, $outputFormat);
  if ($result['exitCode'] == 0) {
    # if no error with bibber, then run on full source with definitions included
    $definitions = ReadPage("Bibtex.Definitions", READPAGE_CURRENT)['text'];
    $result = run_bibber($definitions . $input, $outputFormat);
  }
  return $result;
}

function run_bibber($input, $outputFormat)
{
  global $FarmD;

  $cmd = array("$FarmD/cookbook/bibtexpages/bin/bibber", '-', '-', '--output', $outputFormat, '--sort', "-year,+author,+title", '--latex2html', '--limit-fields');

  $descriptorspec = array(
    0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
    1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
    2 => array("pipe", "w")   // stderr is a pipe that the child will write to 
  );
  $process = proc_open($cmd, $descriptorspec, $pipes);
  $result = array('exitCode' => -1);
  if (is_resource($process)) {
    // $pipes now looks like this:
    // 0 => writeable handle connected to child stdin
    // 1 => readable handle connected to child stdout
    // Any error output will be appended to /tmp/error-output.txt
    fwrite($pipes[0], $input);
    fclose($pipes[0]);
    $result['output'] = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $result['errorText'] = stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    // It is important that you close any pipes before calling
    // proc_close in order to avoid a deadlock
    $result['exitCode'] = proc_close($process);
    // special: bibber throws nothing to stderror; 
    // if an error happens the error message is also sent to stdout
    if ($result['exitCode'] != 0) $result['errorText'] = $result['output'];
    if (empty($result['errorText'])) {
      $result['errorText'] = "error when running translator of bibtex";
    }
  } else {
    $result['errorText'] = "error in running translator of bibtex";
  }
  return $result;
}

function ValidateSource($pagename, &$page, &$new)
{
  global $EnablePost, $MessagesFmt, $HTMLStylesFmt;
  $data = $new['text'];

  # EnablePost only set when pressed 'Save' or 'Save and edit' button
  # We do not validate source when not saving page.
  if (!$EnablePost)  return true;
  # We cancel save if bibtex contains errors.
  $BibtexData = translateBibtex($data);
  $exitCode = $BibtexData['exitCode'];

  // PRETTY PRINTING
  //TODO:
  //  1. make it option with configuration parameter
  //  2. currently  ppbib does also sort source -> not wanted, only formatting
  //  3. extra spaces are added beforer {  -> think not a problem!
  //  4. when parsing ok, but pp give error then
  //         give warning: pp formatting skipped, data is saved as submitted 
  //                       something went wrong in formatting, contact tech support to solve formatting
  // 
  // if ($exitCode == 0) {
  //   # pretty print source;
  //   # if source is valid; then replace source with pretty printed source
  //   $BibtexData = translateBibtex($data, 'ppbib');

  //   # note: translating can be succesful but still an error can happen in pretty printing,
  //   # which  still can be  catch in next if!
  //   $exitCode = $BibtexData['exitCode'];
  //   // only do pp if ppbib succesful
  //   if ($exitCode == 0) {
  //      $new['text'] = $BibtexData['output'];
  //   }
  //   if ($exitCode != 0) {
  //     // ignore pp error
  //     $exitCode=0;
  //   }
  // }
  if ($exitCode != 0) {
    $EnablePost = 0;
    $errorText = $BibtexData['errorText'];
    $MessagesFmt['errorvalidate'] = "<br><b> Page is not saved. </b> <p class='editerror'>ERROR($exitCode): $errorText</p>\n";
    $HTMLStylesFmt['bibtex'] = ".editerror { color:red; font-style:italic; margin-top:1.33em; margin-bottom:1.33em; }\n";
  }
}

function PreviewPageBibtex($pagename, &$page, &$new)
{
  global  $FmtV, $HTMLStylesFmt;
  if (@$_REQUEST['preview']) {
    $data = $new['text'];
    $BibtexData = translateBibtex($data);

    $exitCode = $BibtexData['exitCode'];
    if ($exitCode != 0) {
      $errorText = $BibtexData['errorText'];
      $html = "<p class='editerror'>ERROR($exitCode): $errorText</p>\n";
      $HTMLStylesFmt['bibtex'] = ".editerror { color:red; font-style:italic; margin-top:1.33em; margin-bottom:1.33em; }\n";
    } else {
      $html = $BibtexData['output'];
    }
    $FmtV['$PreviewText'] = $html;
  }
}

function HandleBibtexPageNotFound($pagename)
{
  global $HandleSourceFmt, $PageStartFmt, $PageEndFmt, $FmtV;
  global $DefaultPageTextFmt, $PageNotFoundHeaderFmt, $HTTPHeaders;
  # page not found message
  SDV($DefaultPageTextFmt, '(:include $[{$SiteGroup}.PageNotFound]:)');
  $text = "The page \"$pagename\" doesn't exist. (Create [[$pagename]])";
  SDV($PageNotFoundHeaderFmt, 'HTTP/1.1 404 Not Found');
  SDV($HTTPHeaders['status'], $PageNotFoundHeaderFmt);
  $text = '(:groupheader:)' . @$text . '(:groupfooter:)';
  $FmtV['$PageText'] = MarkupToHTML($pagename, $text);
  SDV($HandleSourceFmt, array(&$PageStartFmt, '$PageText', &$PageEndFmt));
  PrintFmt($pagename, $HandleSourceFmt);
}


function HandleBibtexFormattedInSkin($pagename, $auth = 'edit')
{
  global $HandleSourceFmt, $PageStartFmt, $PageEndFmt, $FmtV, $MessagesFmt;
  if (!PageExists($pagename)) {
    HandleBibtexPageNotFound($pagename, $pagename);
    return;
  }
  $bibtex = ReadPage($pagename, READPAGE_CURRENT)['text'];
  $BibtexData = translateBibtex($bibtex);
  $exitCode = $BibtexData['exitCode'];
  if ($exitCode != 0) {
    $errorText = $BibtexData['errorText'];
    $html = "<h4>Error($exitCode):</h4><pre>$errorText</pre>";
  } else {
    $html = $BibtexData['output'];
    # editing bibtex is different from editing normal wiki pages,
    # therefore we give options to show an action menu specially for bibtex 
    # own action menu; 
    if ($BibtexData['show_bibtex_action_menu']) {
      #$action_menu = "<br><hr><b>Bibtex actions: </b><br> &nbsp;&nbsp;<a href='?action=edit'>edit</a>  &nbsp;&nbsp; <a href='?action=source'>source</a> &nbsp;&nbsp; <a href='?action=rawsource'>raw source</a>  &nbsp;&nbsp; <a href='?action=rawhtml'>raw html</a><hr>";
      $action_menu = "<br><b>Bibtex actions: </b><br> &nbsp;&nbsp;<a href='?action=edit'>edit</a>  &nbsp;&nbsp; <a href='?action=source'>source</a> &nbsp;&nbsp; <a href='?action=rawsource'>raw source</a>  &nbsp;&nbsp; <a href='?action=rawhtml'>raw html</a>";
      $html = $action_menu . $$html;
    }
  }
  $messages = implode('', (array)$MessagesFmt);
  $FmtV['$PageText'] = $messages . $html;
  SDV($HandleSourceFmt, array(&$PageStartFmt, '$PageText', &$PageEndFmt));
  PrintFmt($pagename, $HandleSourceFmt);
}

function HandleBibtexSourceInSkin($pagename, $auth = 'read')
{

  if (!PageExists($pagename)) {
    HandleBibtexPageNotFound($pagename);
    return;
  }
  $bibtexSource = ReadPage($pagename, READPAGE_CURRENT)['text'];
  PrintBibtexSource($pagename, $bibtexSource, $auth);
}

function HandleBibtexRawSource($pagename, $auth = 'read')
{

  if (!PageExists($pagename)) {
    HandleBibtexPageNotFound($pagename);
    return;
  }
  $bibtexSource = ReadPage($pagename, READPAGE_CURRENT)['text'];
  PrintBibtexRawSource($pagename, $bibtexSource, $auth);
}


function HandleBibtexRawHtml($pagename, $auth = 'read')
{

  if (!PageExists($pagename)) {
    HandleBibtexPageNotFound($pagename);
    return;
  }
  $bibtexSource = ReadPage($pagename, READPAGE_CURRENT)['text'];
  $BibtexData = translateBibtex($bibtexSource);
  $exitCode = $BibtexData['exitCode'];
  if ($exitCode != 0) {
    $errorText = $BibtexData['errorText'];
    $html = "<h4>Error($exitCode):</h4><pre>$errorText</pre>";
  } else {
    $html = $BibtexData['output'];
  }
  PrintBibtexRawHtml($pagename, $html, $auth);
}

function PrintBibtexSource($pagename, $bibtexSource, $auth = 'read')
{
  global $HandleSourceFmt, $PageStartFmt, $PageEndFmt, $FmtV;

  if (trim($bibtexSource) == '') {
    $html = 'Page contains no text';
  } else {
    # use prismjs for syntax highlighting source code ; basic usage see https://prismjs.com/#basic-usage
    $html = "<pre id='bibtex'  class='linkable-line-numbers line-numbers'><code class='language-bibtex match-braces'>" . htmlspecialchars($bibtexSource, ENT_QUOTES) . "</code></pre>";
  }
  #$html = "<h2 class='wikiaction'>Source $pagename </h2>&nbsp;&nbsp;<a href='?action=edit'>edit</a> &nbsp;&nbsp; <a href='?'>view</a>  &nbsp;&nbsp;<a href='?action=rawsource'>raw source</a>  &nbsp;&nbsp; <a href='?action=rawhtml'>raw html</a><br><br>" . $html;
  $html = "<h2 class='wikiaction'>Source $pagename </h2>" . $html;
  $FmtV['$PageText'] = "$html";

  SDV($HandleSourceFmt, array(&$PageStartFmt, '$PageText', &$PageEndFmt));
  PrintFmt($pagename, $HandleSourceFmt);
}

function PrintBibtexRawSource($pagename, $bibtexSource, $auth = 'read')
{
  global $HTTPHeaders;

  if (trim($bibtexSource) == '') {
    $bibtexSource = 'Page contains no text';
  }

  foreach ($HTTPHeaders as $h) {
    $h = preg_replace('!^Content-type:\\s+text/html!i', 'Content-type: text/plain', $h);
    header($h);
  }
  echo $bibtexSource;
  exit;
}

function PrintBibtexRawHtml($pagename, $html, $auth = 'read')
{
  global $HTTPHeaders;

  if (trim($html) == '') {
    $html = 'Page contains no text';
  }

  foreach ($HTTPHeaders as $h) {
    #$h = preg_replace('!^Content-type:\\s+text/html!i', 'Content-type: text/plain', $h);
    header($h);
  }
  echo $html;
  exit;
}
