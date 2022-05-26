FROM php:8.1-apache


### composer install
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN apt-get -y update \
&& apt-get -y install git

# Install unzip utility and libs needed by zip PHP extension 
RUN apt-get update && apt-get install -y \
    zlib1g-dev \
    libzip-dev \
    unzip
RUN docker-php-ext-install zip




### node installation 

# replace shell with bash so we can source files
RUN rm /bin/sh && ln -s /bin/bash /bin/sh


# nvm environment variables
ENV NVM_DIR /usr/local/nvm
ENV NODE_VERSION 16.15.0

RUN mkdir $NVM_DIR

# install nvm
# https://github.com/creationix/nvm#install-script
RUN curl https://raw.githubusercontent.com/creationix/nvm/master/install.sh | bash

# install node and npm
RUN source $NVM_DIR/nvm.sh \
    && nvm install $NODE_VERSION \
    && nvm alias default $NODE_VERSION \
    && nvm use default

# add node and npm to path so the commands are available
ENV NODE_PATH $NVM_DIR/v$NODE_VERSION/lib/node_modules
ENV PATH $NVM_DIR/versions/node/v$NODE_VERSION/bin:$PATH

# confirm installation
RUN node -v
RUN npm -v


### server for tests setup
COPY tests/server/ tests/server/
WORKDIR /var/www/html/tests/server
RUN /bin/bash -c "npm install;"
WORKDIR /var/www/html
EXPOSE 4020
CMD [ "node", "tests/server/server.js" ]