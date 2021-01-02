FROM node:15.3.0-buster

WORKDIR /app/

RUN curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | apt-key add -
RUN echo "deb https://dl.yarnpkg.com/debian/ stable main" | tee /etc/apt/sources.list.d/yarn.list
RUN apt-get update -y
RUN apt-get install -y apt-transport-https ca-certificates
RUN apt-get install -y yarn

EXPOSE 5000
