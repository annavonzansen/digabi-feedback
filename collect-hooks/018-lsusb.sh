#!/bin/sh

COMMAND="/usr/bin/lsusb"

if [ -x "${COMMAND}" ]
then
    echo "I: Executing command (${INFILE})."
    ${COMMAND}
else
    echo "E: Can't execute command (${COMMAND})."
fi
