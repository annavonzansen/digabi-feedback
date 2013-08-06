#!/bin/sh

SCRIPTS="$(ls collect-hooks/*.sh)"
LOGFILE="mylog.log"

for s in ${SCRIPTS}
do
    if [ -x "${s}" ]
    then
        echo "# BEGIN: --$(basename ${s})-- #" >>${LOGFILE}
        ${s} >>${LOGFILE}
        echo "# END:   --$(basename ${s})-- #" >>${LOGFILE}
    fi
done
