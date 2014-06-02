#!/bin/sh

COMMAND="/bin/dmesg"

if [ -x "${COMMAND}" ]
then
    echo "I: Executing command (${COMMAND})."
    ${COMMAND} | sed 's/..:..:..:..:..:../[REMOVED]/g' 2>&1
else
    echo "E: Can't execute command (${COMMAND})."
fi
