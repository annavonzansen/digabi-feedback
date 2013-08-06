#!/bin/sh

COMMAND="/usr/bin/lspci"

if [ -x "${COMMAND}" ]
then
    echo "I: Executing command (${INFILE})."
    ${COMMAND}
else
    echo "E: Can't execute command (${COMMAND})."
fi
