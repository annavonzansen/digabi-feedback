#!/bin/sh

COMMAND="/usr/bin/uptime"

if [ -x "${COMMAND}" ]
then
    echo "I: Executing command (${INFILE})."
    ${COMMAND}
else
    echo "E: Can't execute command (${COMMAND})."
fi
