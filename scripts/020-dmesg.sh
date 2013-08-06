#!/bin/sh

COMMAND="/bin/dmesg"

if [ -x "${COMMAND}" ]
then
    echo "I: Executing command (${INFILE})."
    ${COMMAND} |grep -i error
else
    echo "E: Can't execute command (${COMMAND})."
fi
