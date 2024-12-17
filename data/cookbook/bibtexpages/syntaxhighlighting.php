<?php if (!defined('PmWiki')) exit();

function enableSyntaxHighlightingForBibtex()
{
  global $action, $HTMLHeaderFmt, $PubDirUrl, $EnablePmSyntax, $PageEditForm, $HTMLStylesFmt;
  # used prismjs javascript library to syntax highlight bibtex source code
  # downloaded:
  #   -  https://prismjs.com/download.html#themes=prism-solarizedlight&languages=markup+css+clike+javascript&plugins=line-highlight+line-numbers+autolinker+toolbar+copy-to-clipboard+match-braces
  #   -  and https://github.com/SaswatPadhi/prismjs-bibtex  for bibtex support
  #      which is not standard included
  # then used https://live.prismjs.com/ to enable also highlighting in textarea when editing the source. 
  #   - took latest source from https://github.com/PrismJS/live/ 

  $BibtexPagesPubDirUrl = $PubDirUrl . '/bibtexpages/';
  # TODO:  update sources, and should js not be in footer instead as shown in example at https://prismjs.com/#basic-usage
  $HTMLHeaderFmt['BibtexHighlighting'] = '       
      <!--  alternative color scheme <link rel="stylesheet" href="' . $BibtexPagesPubDirUrl . '/prism/prism-solarizedlight.css" /> -->
      <link rel="stylesheet" href="' . $BibtexPagesPubDirUrl . '/prism/prism.css" /> 
      <link rel="stylesheet" href="' . $BibtexPagesPubDirUrl . '/prism/prism-live.css" />

      <!-- online bliss:  <script src="https://blissfuljs.com/bliss.shy.min.js"></script> -->
      <script src="' . $BibtexPagesPubDirUrl . '/prism/bliss.shy.min.js"></script>
       
      <script src="' . $BibtexPagesPubDirUrl . '/prism/prism.js"></script>
      <script src="' . $BibtexPagesPubDirUrl . '/prism/prism-bibtex.js"></script>  
      ';

  if ($action == "edit") {
    $HTMLHeaderFmt['BibtexHighlighting'] =  $HTMLHeaderFmt['BibtexHighlighting'] . '
        <script src="' . $BibtexPagesPubDirUrl . '/prism/prism-live.js"></script>
        <script src="' . $BibtexPagesPubDirUrl . '/prism/prism-live-toggleHighlight.js"></script>
        ';

    # use a special page as edit form:
    $PageEditForm = "Bibtex.EditForm";

    # disable syntax highlighting for pmwiki source
    $EnablePmSyntax = 0;

    # Textarea is (:e_textarea:) in Site.EditForm.
    # To adapt it we take the definition for (:e_textarea:) directive used in Site.EditForm and adapt it 
    # so that it enables syntax highlighting in the textarea of the EditForm.
    # note: works ok in brave but not on safari where the pre and textarea do not overlap, which can be fixed 
    #       in safari by setting line-height of textarea to 19px  instead of the inherited 20px! 
    #       -> probably bug in safari with line-height???  => in prism-live-toggleHighlight.js we disable highlighting by default in safari
    // SDVA($InputTags['e_textarea'], array(
    //   ':html' => "<button  type='button' onClick='toggleHighlight()'>toggle highlight</button><textarea \$InputFormArgs class='prism-live line-numbers language-bibtex fill' style='height: 35em;width:100%;border: 2px dotted rgb(150,150,150);background-color: transparent;'>\$EditText</textarea>\$IncludedPages",
    //   'name' => 'text', 'id' => 'text', 'accesskey' => XL('ak_textedit'),
    //   'rows' => XL('e_rows'), 'cols' => XL('e_cols')));

    Markup('togglehighlightbutton', 'directives', '/\\(:toggle-prism-highlight-button\s*:\\)/', "<button  type='button' onClick='toggleHighlight()'>toggle highlight</button>");
    //DISABLE Toggle// Markup('togglehighlightbutton', 'directives', '/\\(:toggle-prism-highlight-button\s*:\\)/', "");

    // set some extra styles on textarea
    # note: 'textarea:focus {background:white}' in gila.css overrules 'textarea.prism-live {background:transparent}' in prism-live.css because both have same css specificity (https://www.w3schools.com/css/css_specificity.asp) but gila.css is include later in page!
    #       This gave a problem with background transparency of textarea which disapeared when you clicked (focus) on the textarea. Fix is to overrule backgound in local style on element itself!
    // IMPORTANT: both .prism-live and textarea need the same width of border otherwise highlighted text and selected text get misaligned in safari!
    $HTMLStylesFmt['prismlive'] = <<<EOT
          /* set size defaults for content of container  */
          #wikiedit textarea#text {
              height: 35em;
              width:100%;
              border: 1px outset grey; 
              background-color: transparent;  
              overscroll-behavior: none; /* disables bouncing of selected text when fast scrolling */         
          }  
          /* textarea and pre with both prism-live class should have same size  */
          .prism-live {
            height: 35em;
            width:100%; 
            border: 1px outset grey; 
            overscroll-behavior: none;
            
          }  
          
          #wikiedit textarea#text:focus,.prism-live:focus {
            /* border: 2px solid rgb(0 122 255);*/ /* Changes border color to blue when focused */
            /* border: 0px hidden grey;  */
            border: 1px outset grey;
            outline: 3px solid rgb(141 179 251);
            overscroll-behavior: none;
          }
          EOT;
  }
}
