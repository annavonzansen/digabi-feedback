#!/bin/bash

LOGFILES="/var/log/digabi-feedback.log ${HOME}/digabi-feedback.log"
SUBMIT_URL="https://digabi.fi/s/feedback.php"

ATTEMPTS="15"
TIMEOUT="5"

# Source for with_backoff function: <http://stackoverflow.com/questions/8350942/how-to-re-run-the-curl-command-automatically-when-the-error-occurs>
with_backoff () {
  local max_attempts=${ATTEMPTS-5}
  local timeout=${TIMEOUT-1}
  local attempt=0
  local exitCode=0

  while [[ $attempt < $max_attempts ]]
  do
    set +e
    "$@"
    exitCode=$?
    set -e

    if [[ $exitCode == 0 ]]
    then
      break
    fi

    echo "Failure! Retrying in $timeout.." 1>&2
    sleep $timeout
    attempt=$(( attempt + 1 ))
    timeout=$(( timeout * 2 ))
  done

  if [[ $exitCode != 0 ]]
  then
    echo "You've failed me for the last time! ($@)" 1>&2
  fi

  return $exitCode
}

for l in ${LOGFILES}
do
    if [ -r "${l}" ]
    then
        with_backoff curl -F"dup=true" -F"version=1.0" -F"data=@${l}" "${SUBMIT_URL}"
    fi
done
