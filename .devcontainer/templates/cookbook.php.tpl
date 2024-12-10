<?php if (!defined('PmWiki'))
    exit();

$RecipeInfo['AutoTOC']['Version'] = '${CONFIG_COOKBOOK_VERSION}';

Markup(
    'example',
    'directives',
    '/\\(:example:\\)/',
    Keep("<div class='example'><blockquote>This is the <b>result</b> of the (:example:) directive in cookbook/${CONFIG_COOKBOOK_NAME_LC}/${CONFIG_COOKBOOK_NAME_LC}.php</blockquote></div>")
);