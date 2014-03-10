#!/bin/sh

cat /dev/urandom |head -n 10 |md5sum |awk '{print $1}'
