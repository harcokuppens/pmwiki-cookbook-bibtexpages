<?php if (!defined('PmWiki'))
    exit();

$RecipeInfo['AutoTOC']['Version'] = '1.0.0';

Markup(
    'example',
    'directives',
    '/\\(:example:\\)/',
    Keep("<div class='example'><blockquote>This is the <b>result</b> of the (:example:) directive in cookbook/bibtexpages/bibtexpages.php</blockquote></div>")
);