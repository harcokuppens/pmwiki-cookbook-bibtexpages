# create certificate for apache
# src:  we use idea for creation command  from https://github.com/nezhar/php-docker-ssl/blob/master/Dockerfile


mkdir -p cert/
openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout cert/ssl-cert-apache.key -out cert/ssl-cert-apache.pem -subj "/C=NL/ST=Netherlands/L=Nijmegen/O=Radboud University/OU=ICIS SWS/CN=cs.ru.nl"