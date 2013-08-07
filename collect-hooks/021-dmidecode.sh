#!/bin/sh

COMMAND="/bin/dmidecode"

if [ -x "${COMMAND}" ]
then
    echo "I: Executing command (${INFILE})."
    ${COMMAND} -t system 2>&1
else
    echo "E: Can't execute command (${COMMAND})."
fi
