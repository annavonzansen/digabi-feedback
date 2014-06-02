#!/bin/sh

COMMAND="/usr/sbin/dmidecode"

if [ -x "${COMMAND}" ]
then
    echo "I: Executing command (${COMMAND})."
    ${COMMAND} | sed 's/uuid: .*/UUID: [REMOVED]/gi' \
       | sed 's/serial number: .*/Serial Number: [REMOVED]/gi' \
       | sed 's/asset tag: .*/Asset Tag: [REMOVED]/gi' \
       2>&1
else
    echo "E: Can't execute command (${COMMAND})."
fi
