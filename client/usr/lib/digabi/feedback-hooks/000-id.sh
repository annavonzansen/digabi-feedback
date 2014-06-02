#!/bin/sh

ID_FILE="/var/lib/bootid"

if [ -f "${ID_FILE}" ]
then
    BOOT_ID="$(cat ${ID_FILE})"
else
    BOOT_ID="$(cat /dev/urandom |head -n 10 |md5sum |awk '{print $1}')"
    echo ${BOOT_ID} >${ID_FILE}
fi
echo "Boot-ID: ${BOOT_ID}"
