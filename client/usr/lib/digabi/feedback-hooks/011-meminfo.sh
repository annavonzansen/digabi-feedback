#!/bin/sh

INFILE="/proc/meminfo"

if [ -r "${INFILE}" ]
then
    echo "I: Reading input file (${INFILE})."
    cat ${INFILE} |egrep "^MemTotal|MemFree"
else
    echo "E: Can't read input file (${INFILE})."
fi
