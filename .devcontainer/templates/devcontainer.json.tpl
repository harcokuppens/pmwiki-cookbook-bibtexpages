{
  //  
  // open project from folder in vscode with command 
  //    'Dev Containers: Open folder in Container...'
  // or from command line when in folder:
  //    code . 
  //
  // docs: https://containers.dev/implementors/json_reference/
  "name": "pmwiki-cookbook-${CONFIG_COOKBOOK_NAME_LC}",
  "dockerComposeFile": ["./docker-compose.yml"],
  "service": "pmwiki",
  "shutdownAction": "none", // the supporting tools do not stop the containers when the related tool window is closed / shut down
  
  // Open the sub-folder with the source code
  "workspaceFolder": "/var/www/html/pmwiki",

  // https://github.com/devcontainers/spec/blob/main/docs/specs/devcontainerjson-reference.md
  // use remoteUser to make vscode use that user to login to container, however container still runs under the user configure in Dockerfile/docker-compose.yml
  "remoteUser": "www-data",
  //"containerUser": "www-data", // set the user to run the container with; same as USER in Dockerfile or user in docker-compose.yml

  "customizations": {
    "vscode": {
      "extensions": [
        "bmewburn.vscode-intelephense-client",
        "xdebug.php-debug"
      ]
    }
  }
}
