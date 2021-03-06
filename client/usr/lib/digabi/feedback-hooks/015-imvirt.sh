#!/bin/sh

COMMAND="/usr/bin/imvirt"

if [ -x "${COMMAND}" ]
then
    echo "I: Executing command (${COMMAND})."
    ${COMMAND} 2>&1
else
    echo "E: Can't execute command (${COMMAND})."
fi
