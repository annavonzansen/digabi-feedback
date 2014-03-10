#!/bin/sh

INFILE="/proc/cpuinfo"

if [ -r "${INFILE}" ]
then
    echo "I: Reading input file (${INFILE})."
    cat ${INFILE} |egrep "^(processor|vendor_id|model name|cpu MHz)"
else
    echo "E: Can't read input file (${INFILE})."
fi
