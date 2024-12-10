# docs: https://docs.docker.com/reference/compose-file/
name: pmwiki-${CONFIG_COOKBOOK_NAME_LC}-compose
services:
  pmwiki:
    container_name: pmwiki-${CONFIG_COOKBOOK_NAME_LC}-ctr
    image: pmwiki-${CONFIG_COOKBOOK_NAME_LC}-img
    build:
      # context dir used in image build as root path for paths in Dockerfile
      # Path relative to this file.
      # https://docs.docker.com/reference/compose-file/build/#context
      context: ../
      # build with this Dockerfile ( path specified from context dir)
      # https://docs.docker.com/reference/compose-file/build/#dockerfile
      dockerfile: .devcontainer/pmwiki/Dockerfile
      # uncomment next lines to change params used in Dockerfile to build pmwiki image
      args:
        PMWIKI_VERSION: "2.3.37" # newest version of pmwiki, not working with markup_e
      #   PMWIKI_VERSION: '2.3.9' # old version still working with markup_e
      #   PMWIKI_DIR: '/var/www/html/pmwiki' # install pmwiki in subdir
    ports:
      # - "80:80"    # does NOT need vmnetd service(macos), but needs NET_BIND_SERVICE
      # - "443:443"  # does NOT need vmnetd service(macos), but needs NET_BIND_SERVICE
      # - "127.0.0.1:80:80" # needs NET_BIND_SERVICE and needs vmnetd service(macos) running because priviliged port to specific interface
      # - "127.0.0.1:443:443" # needs NET_BIND_SERVICE and needs vmnetd service(macos) running because priviliged port to specific interface
      - "127.0.0.1:8080:80" # does NOT need vmnetd service(macos) nor NET_BIND_SERVICE (because none-priviliged port)
      - "127.0.0.1:8443:443" # does NOT need vmnetd service(macos) nor NET_BIND_SERVICE (because none-priviliged port)
    # cap_add:
    #  - NET_BIND_SERVICE
    environment:
      - COOKBOOK=${CONFIG_COOKBOOK_NAME_LC}
    # https://docs.docker.com/reference/compose-file/services/#user
    # https://docs.docker.com/reference/dockerfile/#user
    #user: "www-data"
    volumes:
      - type: bind
        source: ../data/wiki.d/
        target: /var/www/html/pmwiki/wiki.d/
      - type: bind
        source: ../data/uploads/
        target: /var/www/html/pmwiki/uploads/
      - type: bind
        source: ../data/local/
        target: /var/www/html/pmwiki/local/
      - type: bind
        source: ../data/cookbook/${CONFIG_COOKBOOK_NAME_LC}
        target: /var/www/html/pmwiki/cookbook/${CONFIG_COOKBOOK_NAME_LC}
      - type: bind
        source: ../data/pub/${CONFIG_COOKBOOK_NAME_LC}
        target: /var/www/html/pmwiki/pub/${CONFIG_COOKBOOK_NAME_LC}
