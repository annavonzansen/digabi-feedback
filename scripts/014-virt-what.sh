#!/bin/sh

COMMAND="virt-what"

if [ -x "${COMMAND}" ]
then
    echo "I: Executing command (${INFILE})."
    ${COMMAND}
else
    echo "E: Can't execute command (${COMMAND})."
fi
