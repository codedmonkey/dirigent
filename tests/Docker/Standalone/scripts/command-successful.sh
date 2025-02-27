#!/usr/bin/env sh

# Runs the passed arguments as a command and checks if
# the exit code is 0 (success). If the command was not
# successful, it returns the command stdout + stderr.

OUTPUT=$("$@" 2>&1)

if [ $? -eq 0 ]; then
  echo "0"
else
  echo $OUTPUT
fi
