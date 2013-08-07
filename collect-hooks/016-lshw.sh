#!/bin/sh

COMMAND="lshw"

if [ -x "${COMMAND}" ]
then
    echo "I: Executing command (${INFILE})."
    ${COMMAND} 2>&1
else
    echo "E: Can't execute command (${COMMAND})."
fi
