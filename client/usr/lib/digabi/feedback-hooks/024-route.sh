#!/bin/sh

COMMAND="/sbin/route"

if [ -x "${COMMAND}" ]
then
    echo "I: Executing command (${COMMAND})."
    ${COMMAND} -n \
       | sed -e 's/255/*/gi' \
       | sed -e 's/[0-9][0-9][0-9]/XXX/gi' \
       | sed -e 's/[0-9][0-9]\([ .]\)/XX\1/gi' \
       | sed -e 's/[1-9]\([ .]\)/X\1/gi' \
       2>&1
else
    echo "E: Can't execute command (${COMMAND})."
fi
