#!/bin/sh

COMMAND="/usr/bin/lshw"

if [ -x "${COMMAND}" ]
then
    echo "I: Executing command (${COMMAND})."
    ${COMMAND} | sed 's/serial: .*/serial: [REMOVED]/g' 2>&1
else
    echo "E: Can't execute command (${COMMAND})."
fi
