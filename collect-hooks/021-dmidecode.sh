#!/bin/sh

COMMAND="/usr/sbin/dmidecode"

if [ -x "${COMMAND}" ]
then
    echo "I: Executing command (${COMMAND})."
    ${COMMAND} 2>&1 |grep -v "Serial Number"
else
    echo "E: Can't execute command (${COMMAND})."
fi
