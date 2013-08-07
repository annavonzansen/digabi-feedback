#!/bin/sh

COMMAND="/bin/dmesg"

if [ -x "${COMMAND}" ]
then
    echo "I: Executing command (${INFILE})."
    ${COMMAND} 2>&1 |grep -i error
else
    echo "E: Can't execute command (${COMMAND})."
fi
