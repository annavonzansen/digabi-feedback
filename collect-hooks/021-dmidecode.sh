#!/bin/sh

COMMAND="/usr/sbin/dmidecode"

if [ -x "${COMMAND}" ]
then
    echo "I: Executing command (${COMMAND})."
    ${COMMAND} -t system 2>&1
else
    echo "E: Can't execute command (${COMMAND})."
fi
