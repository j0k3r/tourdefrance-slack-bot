#!/bin/bash

./build.sh

docker kill tdf 

docker rm -v tdf 

docker run --restart=always -d --name tdf -v /tmp:/tmp cuotos/tdf

docker logs -f tdf
