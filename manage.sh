#!/bin/bash

if [ "$1" == "build" ]; then
    echo "building image"
    docker build -t horse-sp-php .
elif [ "$1" == "run" ]; then
    docker run -t --rm --name horseSPphpApp -v "$PWD":/usr/src/horseSP horse-sp-php
else
    echo "build: build the Docker image"
    echo "run: build the app inside Docker container based on image"
fi
