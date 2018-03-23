#NOT DEPLOYED WITH DOCKER, ONLY USED FOR TESTING PURPOSES.

FROM ubuntu:14.04
ENV DEBIAN_FRONTEND noninteractive

# The user id from the id -u command (we want build + run to exec same as OS user using invoking the run/build).
ARG DOCKER_USER_ID=1000

#Common Server Installs
RUN apt-get update && \
    apt-get install -y \
    php5 \
    curl \
    acl \
    htop \
    php5-curl \
    php5-common \
    php5-json \
    php5-intl \
    php5-xdebug \
    make

RUN sed -i 's|;?date.timezone = .*|date.timezone = America/Detroit|g' /etc/php5/cli/php.ini && \
    sed -i 's|;?memory_limit = 128M|memory_limit = 512M|g' /etc/php5/cli/php.ini && \
    sed -i 's|;?error_log = .*|error_log = /dev/stderr|g' /etc/php5/apache2/php.ini

VOLUME ["/var/www"]

# Create working directory
WORKDIR /var/www
