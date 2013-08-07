#!/bin/sh

COMMAND="/usr/bin/uptime"

if [ -x "${COMMAND}" ]
then
    echo "I: Executing command (${COMMAND})."
    ${COMMAND}
else
    echo "E: Can't execute command (${COMMAND})."
fi
