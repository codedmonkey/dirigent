#!/usr/bin/env sh

OUTPUT=$("$@" 2>&1)

if [ $? -eq 0 ]; then
    echo 0
else
    echo $OUTPUT
fi
