#!/bin/sh

VERSION_FILE="/etc/digabi-version"

if [ -f "${VERSION_FILE}" ]
then
	echo "Digabi-Version: $(cat ${VERSION_FILE})"
else
	echo "E: Digabi version file not found."
fi
