# syntax=docker/dockerfile:1.4

########################################################################################
#    basic pmwiki setup 
########################################################################################


#-----------------------------------------------
#  configuration  by docker arguments
#-----------------------------------------------

# idea from https://github.com/kristophjunge/docker-test-saml-idp use env. variables to possibly override sp in idp
# improvement: use build ARG instead ENV because ARG works over multiple stages, and ENV only in on stage

# versions installed:
ARG PMWIKI_VERSION='2.3.7'

# install pmwiki into 'pmwiki' subdir of website's rootfolder
ARG PMWIKI_DIR='/var/www/html/pmwiki'


#-----------------------------------------------
#  setup apache with ssl and php
#-----------------------------------------------

# use base php apache image which by default only supports http (no https)
# ------------------------------------------------------------------------
FROM php:7.4-apache
# src:
#    php https://github.com/docker-library/docs/blob/master/php/README.md#apache-with-a-dockerfile
#
#    apache config in /etc/apache2/apache2.conf
#    Dockerfile at: 
#     https://github.com/docker-library/php/blob/f2b879649fb8238dfa96592c49cd676601ee33a1/7.4/bullseye/apache/Dockerfile
#      which contains the lines: 
#          ENTRYPOINT ["docker-php-entrypoint"]
#          # https://httpd.apache.org/docs/2.4/stopping.html#gracefulstop
#          STOPSIGNAL SIGWINCH
#
#          COPY apache2-foreground /usr/local/bin/
#          WORKDIR /var/www/html
#
#          EXPOSE 80
#          CMD ["apache2-foreground"]
#
# it also defines
#    ENV PHPIZE_DEPS \
#            autoconf \
#            dpkg-dev \
#            file \
#            g++ \
#            gcc \
#            libc-dev \
#            make \
#            pkg-config \
#            re2c
#    ENV PHP_INI_DIR /usr/local/etc/php
#    ENV APACHE_CONFDIR /etc/apache2
#    ENV APACHE_ENVVARS $APACHE_CONFDIR/envvars
#    ENV PHP_CFLAGS="-fstack-protector-strong -fpic -fpie -O2 -D_LARGEFILE_SOURCE -D_FILE_OFFSET_BITS=64"
#    ENV PHP_CPPFLAGS="$PHP_CFLAGS"
#    ENV PHP_LDFLAGS="-Wl,-O1 -pie"
#
#    ENV GPG_KEYS 42670A7FE4D0441C8E4632349E4FDC074A4EF02D 5A52880781F755608BF815FC910DEB46F53EA312
#
#    ENV PHP_VERSION 7.4.29
#    ENV PHP_URL="https://www.php.net/distributions/php-7.4.29.tar.xz" PHP_ASC_URL="https://www.php.net/distributions/php-7.4.29.tar.xz.asc"
#    ENV PHP_SHA256="7d0f07869f33311ff3fe1138dc0d6c0d673c37fcb737eaed2c6c10a949f1aed6"
#
# for debugging we install
#--------------------------
RUN apt-get update && apt-get -y install vim less iproute2 iputils-ping tree rsync ssh git python3

# configure caching of ssh password, so you only have to supply password first time per host name
# https://superuser.com/questions/1362894/cache-the-password-if-ssh-keys-are-forbidden
#  `-> based on connection reuse => so works only per hostname; when connecting to other host you 
#      again have to supply password
RUN mkdir -p /root/.ssh/
COPY <<EOF /root/.ssh/config
Host *
    ControlPath ~/.ssh/S.%r@%h:%p
    ControlMaster auto
    ControlPersist 100000h

EOF

# Add Marker env variable so that code in container can recognize it is running inside a docker container.
# Usefull for special cases where we have to do something special in the docker case.
# eg. the Saml server used in docker uses a different authid then the one in production.
ENV RUNNING_IN_DOCKER_CONTAINER="true"  

# Dockerfile in repo at .devcontainer/pmwiki/Dockerfile
# docker-compose.yml in repo at .devcontainer/docker-compose.yml
#
# # .devcontainer/docker-compose.yml has for pmwiki service:
# #       context: pmwiki/
# #       dockerfile: ./Dockerfile 
# with this context we get
#     ENV REPO_DIR="../../"         
#     ENV DOCKERFILE_DIR="./"            
# using this we later do
#     COPY ${REPO_DIR}  ${PMWIKI_DIR}
# ABOVE HAS PROBLEM: COPY command does not allow .. arguments 
# => from references: The <src> path must be inside the context of the build; 
#    you cannot COPY ../something /something, because the first step of a docker build 
#    is to send the context directory (and subdirectories) to the docker daemon.
# so instead we have to configure the context as the root of our  repository:
# .devcontainer/docker-compose.yml has for pmwiki service:
#       context: ../
#       dockerfile: .devcontainer/pmwiki/Dockerfile  
ENV REPO_DIR="./"  
ENV DOCKERFILE_DIR=".devcontainer/pmwiki/" 

#  enable ssl
#-----------------------------------------------
# note: we use ideas from https://github.com/nezhar/php-docker-ssl/blob/master/Dockerfile

# 1. install certificates for apache
#
# create certificate for apache 
#RUN openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout /etc/ssl/private/ssl-cert-snakeoil.key -out /etc/ssl/certs/ssl-cert-snakeoil.pem -subj "/C=NL/ST=Netherlands/L=Nijmegen/Radboud University/OU=ICIS SWS/CN=cs.ru.nl"
#
# Instead above we create certificate outside docker and copy certificate into docker image
# because then we keep using the same certificate when rebuilding image. 
# Otherwise it will change per build, and you have to reaccept that selfsigned cert. in your browser.
# So each website needs unique certificate so we create it outside of docker, and copy it into the image:
COPY ${DOCKERFILE_DIR}/cert/ssl-cert-apache.key /etc/ssl/private/ssl-cert-snakeoil.key
COPY ${DOCKERFILE_DIR}/cert/ssl-cert-apache.pem /etc/ssl/certs/ssl-cert-snakeoil.pem


# 2. configure apache
#
# below we use a stripped and patched version from image's default file /etc/apache2/sites-available/default-ssl.conf
# by copying config file in /etc/apache2/sites-enabled/ we enable a ssl website config
COPY <<EOF /etc/apache2/sites-enabled/default-ssl.conf
<IfModule mod_ssl.c>
    <VirtualHost _default_:443>
        ServerAdmin webmaster@localhost

        DocumentRoot /var/www/html
        
        # below commented, because we use default of apache base image that logging is done to stdout/stderr
        #ErrorLog ${APACHE_LOG_DIR}/error.log
        #CustomLog ${APACHE_LOG_DIR}/access.log combined

        SSLEngine on

        SSLCertificateFile    /etc/ssl/certs/ssl-cert-snakeoil.pem
        SSLCertificateKeyFile /etc/ssl/private/ssl-cert-snakeoil.key


        <FilesMatch "\.(cgi|shtml|phtml|php)$">
                SSLOptions +StdEnvVars
        </FilesMatch>
        <Directory /usr/lib/cgi-bin>
                SSLOptions +StdEnvVars
        </Directory>

    </VirtualHost>
</IfModule>
EOF

# fix that apache returns svgz with right content type on http get request, so
# that browser knows how to load it correctly:
RUN echo 'AddType image/svg+xml svg svgz' >> /etc/apache2/apache2.conf\
    && echo 'AddEncoding gzip svgz' >> /etc/apache2/apache2.conf
# reload apache: /etc/init.d/apache2 reload  


RUN a2enmod rewrite
## next line not needed because by copying config file in /etc/apache2/sites-enabled/ we already enabled the ssl config
#RUN a2ensite default-ssl
RUN a2enmod ssl

# https://github.com/docker-library/docs/blob/master/php/README.md#configuration
# Use the default production configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"


# KISS: only support https access (http closed,no redirect needed!)
# EXPOSE 80 # note: already in "php:7.4-apache" base image, but we don't use it!
EXPOSE 443




# ----------------------------------------------------------------
# install pmwiki : https://www.pmwiki.org/wiki/PmWiki/Installation
# ----------------------------------------------------------------

# declare args in local phase to get default arg values set from top of Dockerfile
ARG PMWIKI_DIR
# Make persist in container as environment variables
ENV PMWIKI_DIR="${PMWIKI_DIR}" 

# # 0. add extra info file for info about used php server
# COPY <<EOF "${PMWIKI_DIR}/../phpinfo.php"
# <?php

# // Show all information, defaults to INFO_ALL
# phpinfo();
# EOF

# 1. copy pmwiki source 
ARG PMWIKI_VERSION
ENV PMWIKI_VERSION="${PMWIKI_VERSION}"
RUN mkdir -p ${PMWIKI_DIR} 
RUN curl -s -L https://www.pmwiki.org/pub/pmwiki/pmwiki-${PMWIKI_VERSION}.tgz --output - | tar  -xz --strip-components 1  -C ${PMWIKI_DIR}    


# 2. use .htaccess file to do redirection of page as an argument to script pmwiki.php in the ${PMWIKI_DIR} dir
#    This is done in two steps:
# a) .htaccess file which does redirect requests from root folder into ${PMWIKI_DIR} dir   
COPY <<'EOF' /var/www/html/.htaccess
RewriteEngine On

# # src: https://cwiki.apache.org/confluence/display/HTTPD/RewriteHTTPToHTTPS
# # This checks to make sure the connection is not already HTTPS
# # and if not on https then redirect to https
# RewriteCond %{HTTPS} !=on
# RewriteRule ^/?(.*) https://%{SERVER_NAME}:9443/$1 [R,L]


# Send requests without parameters to pmwiki/
RewriteRule ^$           pmwiki/  [L]

# Send requests starting with capital letter or digit to pmwiki/
RewriteCond %{REQUEST_URI} !^/pmwiki(.*)$
RewriteRule ^([A-Z0-9\xa0-\xff].*) pmwiki/$1  [QSA,L]
EOF

# adapt .htaccess file for changes in subdir of pmwiki installation
RUN subdir=${PMWIKI_DIR#/var/www/html/} && subdir=${subdir%/} && sed -i "s#pmwiki#$subdir#g" /var/www/html/.htaccess


# b) .htaccess file which does redirect of requests in ${PMWIKI_DIR} as argument to pmwiki.php script
# NOTE: if pmwiki installed in rootdir of webserver(/var/www/html) then this file overwrites above .htaccess which is fine
COPY <<'EOF' ${PMWIKI_DIR}/.htaccess
# https://www.pmwiki.org/wiki/Cookbook/CleanUrls
# Use mod_rewrite to enable "Clean URLs" for a PmWiki installation.
RewriteEngine On

# Send requests without parameters to pmwiki.php.
RewriteRule ^$           pmwiki.php  [L]
# Send requests for index.php to pmwiki.php.
RewriteRule ^index\.php$ pmwiki.php  [L]

# Add Help alias: redirect Help.* to  PmWiki.*
RewriteRule ^Help(.*)$ pmwiki.php?n=PmWiki$1  [QSA,L]

# Send requests to pmwiki.php, appending the query string part.
RewriteRule ^([A-Z0-9\xa0-\xff].*)$ pmwiki.php?n=$1  [QSA,L]
EOF


# 3. copy source of pmwiki svn repo in /var/www/html/pmwiki
#COPY ${REPO_DIR}  ${PMWIKI_DIR}
# only copy data from data folder as default data when binding not used.
COPY ${REPO_DIR}/data  ${PMWIKI_DIR}/

# 4. fix owner and write permissions
RUN chown -R www-data:www-data /var/www/html/
RUN chmod -R a+rwX /var/www/html/

# may you login as root to container via docker exec then we must be sure new files are rw by world, 
# because otherwise www-data user cannot read/write it. We do this by setting umask 
# for root user to 'u=rwx,g=rwx,o=rwx'. (files by default rw-rw-rw permission and directories rwxrwxrwx permission) 
RUN echo "umask 000" >> /etc/profile

# 5. install pmwiki-import script in container
#      user can open terminal in container and can easily setup container to be exactly like a website in production;
#      The given ssh path argument determines the remote pmwiki production site from which data and config is mirrored.
#      Note: ssh credentials for git can be accessed via ssh-agent on host in vscode terminal
COPY ${DOCKERFILE_DIR}/bin/pmwiki_mirror_remote_site /usr/bin/pmwiki_mirror_remote_site
RUN chmod a+x /usr/bin/pmwiki_mirror_remote_site
# install dependencies of pmwiki_mirror_remote_site script:
# install "mirror" cmdline utility to easily mirror files into container (wrapper of rsync using ssh)
RUN curl -sL --output /usr/bin/mirror  https://raw.githubusercontent.com/harcokuppens/mirror/v1.1.2/bin/mirror
RUN chmod a+x /usr/bin/mirror 

# install python programs to import or export pmwiki pages from or to text files
COPY ${DOCKERFILE_DIR}/bin/pmwiki_exportfile /usr/bin/pmwiki_exportfile
RUN chmod a+x /usr/bin/pmwiki_exportfile
COPY ${DOCKERFILE_DIR}/bin/pmwiki_importfile /usr/bin/pmwiki_importfile
RUN chmod a+x /usr/bin/pmwiki_importfile


#---------------------------------------------------------------
#  add xdebug debugging support  
#---------------------------------------------------------------


# add xdebug 3.1.6 
# -----------------


RUN pecl install xdebug-3.1.6 && docker-php-ext-enable xdebug


COPY <<'EOF' /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini 
# IMPORTANT : after changing an option 
#    first check for errors in terminal with command :                  php -v 
#    then reload the config in the apache server with the command:     /etc/init.d/apache2 reload    


# overrule settings in php.ini 
#display_errors = On   # default value
# instead of blank screen on error, it now outputs error info in browser window

# enable xdebug extension in apache webserver
zend_extension=xdebug

# configure new xdebug 3.1.6 -- which is latest xdebug version still supporting php 7.4; newer versions require at least php 8.0
xdebug.mode = debug
xdebug.start_with_request = yes
#xdebug.client_host = localhost
# note: localhost  is already default value
#xdebug.client_port = 9003
# note: 9003 is already default value

# print extra output when error occurs:   same config params used for old as new xdebug
xdebug.show_local_vars=1
xdebug.dump_undefined=1

# with dump_globals option you can complete disable printing of all globals, but if enabled you still need to specify which globals to print:
xdebug.dump_globals=1  
xdebug.dump.POST=*
xdebug.dump.GET=*
# xdebug.dump.SERVER=HTTP_HOST, SERVER_NAME
# xdebug.dump.SERVER=*

# overrule settings in php.ini ; by default we use production
xdebug.force_display_errors = 0
# instead of blank screen on error, it now outputs error info in browser window

# finetune what errors/warnings/notices to show -> seems still problemetic to get it right
#xdebug.force_error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE & ~E_WARNING
#xdebug.force_error_reporting = E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_WARNING
#xdebug.force_error_reporting = E_ERROR | E_WARNING | E_PARSE
#xdebug.force_error_reporting = E_ERROR | E_PARSE
#xdebug.force_error_reporting = E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_WARNING & ~E_USER_DEPRECATED
xdebug.force_error_reporting = 1
EOF

# add default launch.json for xdebug for vscode
# ---------------------------------------------

RUN mkdir -p /var/www/html/pmwiki/.vscode/
COPY <<'EOF' /var/www/html/pmwiki/.vscode/launch.json  
{
    // Use IntelliSense to learn about possible attributes.
    // Hover to view descriptions of existing attributes.
    // For more information, visit: https://go.microsoft.com/fwlink/?linkid=830387
    "version": "0.2.0",
    "configurations": [
        {
            "name": "Listen for Xdebug",
            "type": "php",
            "request": "launch",
            "port": 9003
        },
        {
            "name": "Launch currently open script",
            "type": "php",
            "request": "launch",
            "program": "${file}",
            "cwd": "${fileDirname}",
            "port": 0,
            "runtimeArgs": [
                "-dxdebug.start_with_request=yes"
            ],
            "env": {
                "XDEBUG_MODE": "debug,develop",
                "XDEBUG_CONFIG": "client_port=${port}"
            }
        }
    ]
}
EOF

#  fix owner and write permissions
RUN chown -R www-data:www-data /var/www
RUN chmod -R a+rwX /var/www
# note: when vscode logs in as remoteUser `www-data` it writes its remote server
#       files in /var/www/.vscode-server/ as the `www-data` user.

WORKDIR  ${PMWIKI_DIR}

# to make you login as www-data user (which has by default as shell /usr/sbin/nologin)
RUN usermod -s /bin/bash www-data
# # to make container runnable as user www-data; make private certificate readable for www-data user 
RUN chmod -R a+rX /etc/ssl/private/
# USER www-data
# src: https://docs.docker.com/reference/dockerfile/#user


