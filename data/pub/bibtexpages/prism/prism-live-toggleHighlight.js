// written by Harco Kuppens

// we disable highlight by making the transparant text in the textarea black again
// The underlying div with highlighted text is overlayed with the black text in the textarea,
// only the line numbers on the left of the div are still visible.
function disableHighlight() {
   p = document.querySelectorAll('.prism-live');
   textarea = p[2];
   textarea.style.color = "black";
   // textarea.style.zIndex="100";  => not needed:
   // textarea already has higher z-index then highlighted code block => needed to get input!
};

// undo the disable highlight by making textarea transparent again
function enableHighlight() {
   p = document.querySelectorAll('.prism-live');
   textarea = p[2];
   textarea.style.color = "transparent";
};



highlightEnabled = true;
function toggleHighlight() {
   if (highlightEnabled) {
      disableHighlight();
   } else {
      enableHighlight();
   };
   highlightEnabled = !highlightEnabled;
}



// disable highlighting after load when using safari because it has a bug in highlighting
//  -> disabling on safari did not help ,still misalignment -> HACKY FIX below was the solution!
document.addEventListener('DOMContentLoaded', () => {

   //// below code did toggle highlight to off for safari!
   //// => disabled because we got problem with safari fixed
   // let userAgentString=navigator.userAgent;
   // let chromeAgent = userAgentString.indexOf("Chrome") > -1;

   // let safariAgent = userAgentString.indexOf("Safari") > -1;

   // // Discard Safari since it also matches Chrome
   // if ((chromeAgent) && (safariAgent)) safariAgent = false;

   // // disable disabling highlighting for safari
   // if (safariAgent) toggleHighlight(); 


   // HACKY FIX:
   // set line-height to 1.5 instead of 20px fixes problem in safari that  colored overlay lines where not in sync with lines in text area
   // note: we apply it for all browsers because all browsers just support lineHeight = "1.5" well.
   // we need a timeout to wait for prism has applied its styles, which we then afterwards can correct
   setTimeout(() => { p = document.querySelectorAll('.prism-live'); outerdiv = p[0]; outerdiv.style.lineHeight = "1.5"; }, 1000)


   window.addEventListener("resize", adaptTextareaWithWindowSize);
   adaptTextareaWithWindowSize();
});



// Function for Event Listener
function adaptTextareaWithWindowSize() {
   // Acquiring height
   var documentheight = document.documentElement.clientHeight;
   // calibrate height of header and footer arround text edit field for code 
   // headerwithfooterheight=350;
   headerwithfooterheight = 450;
   // maximize height of text edit field where it keeps buttons below it on screen
   p = document.querySelectorAll('.prism-live');
   outerdiv = p[0];
   codediv = p[1];
   textarea = p[2];
   newheight = documentheight - headerwithfooterheight;
   outerdiv.style.height = newheight + "px";
   codediv.style.height = newheight + "px";
   textarea.style.height = newheight + "px";

   // HACKY FIX again! (see above)
   // lineHeight needed to be set again, gets changed back to default after resize 
   //  (to fix outlining of colored lines and lines in textarea which get broken after resize.)
   outerdiv.style.lineHeight = "1.5";
}

