#!/bin/sh

SCRIPTS="$(ls collect-hooks/*.sh)"
LOGFILE="/var/log/digabi-feedback.log"

if [ ! -w "${LOGFILE}" ]
then
    LOGFILE="${HOME}/digabi-feedback.log"
fi

if [ -f "${LOGFILE}" ]
then
    echo "I: Logfile already exists, will not rewrite => exiting."
    exit 1
fi

for s in ${SCRIPTS}
do
    if [ -x "${s}" ]
    then
        echo "# BEGIN: --$(basename ${s})-- #" >>${LOGFILE}
        ${s} >>${LOGFILE}
        echo "# END:   --$(basename ${s})-- #" >>${LOGFILE}
    fi
done
echo "I: Log saved to ${LOGFILE}."
