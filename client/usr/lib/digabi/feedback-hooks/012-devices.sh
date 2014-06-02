#!/bin/sh

INFILE="/proc/devices"

if [ -r "${INFILE}" ]
then
    echo "I: Reading input file (${INFILE})."
    cat ${INFILE}
else
    echo "E: Can't read input file (${INFILE})."
fi
