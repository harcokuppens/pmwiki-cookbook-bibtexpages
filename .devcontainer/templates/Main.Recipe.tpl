version=pmwiki-2.3.37 ordered=1 urlencoded=1
text=%25define=recipeinfo color=black background-color=#f7f7f7 border='1px solid #cccccc' padding=4px%25%0a%0a>>recipeinfo%3c%3c%0aSummary: %0aVersion:   ${CONFIG_COOKBOOK_VERSION} %0aStatus:  In active use%0aMaintainer:   ${CONFIG_COOKBOOK_AUTHOR} %0aLicense:  BSD-3-clause%0aCategories:  PUT-HERE-CATEGORIES eg. [[!Images]] %0a>>%3c%3c%0a%0a!! Questions answered by this recipe%0a%0aHow can I ... ? %0a%0a!!Description%0a%0a%25color=red%25 PUT HERE A SHORT DESCRIPTION OF THE COOKBOOK. THE DETAILS ARE AT RECIPE PAGE.%0a%0aThe source code of this cookbook is at [[${CONFIG_COOKBOOK_REPO_URL}| the cookbook's repository]], which also provides%0aa devcontainer in which you can see the plugin in action and further develop it. %0a%0a%25color=red%25 DOCUMENT HERE THE DETAILS OF YOUR COOKBOOK EG. A DIRECTIVE ETC%0a%0a[[#install]]%0a!! Installation%0a%0aDownload Attach:${CONFIG_COOKBOOK_NAME_LC}-${CONFIG_COOKBOOK_VERSION}.zip, unpack the files to the associated directories, and add to config.php:%0a%0a->[@include_once("$FarmD/cookbook/${CONFIG_COOKBOOK_NAME_LC}/${CONFIG_COOKBOOK_NAME_LC}.php");@]%0a%0a!! Notes%0a%0a%0a!! Release Notes%0a%0a* ${CONFIG_COOKBOOK_VERSION}: initial version - Attach:${CONFIG_COOKBOOK_NAME_LC}-${CONFIG_COOKBOOK_VERSION}.zip - [[~${CONFIG_COOKBOOK_AUTHOR} ]] %0a%0a!! See Also%0a%0a* ...%0a%0a!! Contributors%0a%0a[[~${CONFIG_COOKBOOK_AUTHOR} ]]%0a%0a%0a!! Comments%0aSee discussion at [[{$Name}-Talk]]