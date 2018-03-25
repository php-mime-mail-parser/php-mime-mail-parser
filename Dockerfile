FROM ubuntu:14.04

# Install packages for building ruby
RUN apt-get update

RUN apt-get install -y --force-yes php
