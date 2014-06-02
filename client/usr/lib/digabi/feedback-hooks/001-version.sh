#!/bin/sh

VERSION_FILE="/var/lib/digabi/version"

if [ -f "${VERSION_FILE}" ]
then
	echo "Digabi-Version: $(cat ${VERSION_FILE})"
else
	echo "E: Digabi version file not found."
fi
