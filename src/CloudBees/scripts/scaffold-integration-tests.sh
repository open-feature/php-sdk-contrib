#!/usr/bin/env bash

PORT="4444"
CONTAINER_NAME="roxy-integration-test-server"

##########################
#                        #
# Roxy Server Management #
#                        #
##########################

function server::start_roxy() {
  docker run --rm --name $CONTAINER_NAME -p $PORT:3333 -d rollout/roxy:latest
}

function server::shutdown_roxy() {
  docker stop $CONTAINER_NAME
}

function server::await_roxy() {
  max_tries=30
  while ! client::get_flags 1>/dev/null 2>/dev/null
  do
    max_tries=$(( $max_tries - 1 ))
    if [ $max_tries -le 0 ]; then
      return 1
    fi

    sleep 1
  done

  return 0
}

####################
#                  #
# Roxy Client Code #
#                  #
####################

function client::get_flags() {
  client::__send GET /
}

function client::get_flag() {
  flagName="$1"

  if [ -z "$flagName" ]; then
    main::end_script 1 "Cannot get a flag without the name!"
  fi

  client::__send GET "/$flagName"
}

function client::delete_all_flags() {
  client::__send DELETE /
}

function client::delete_flag() {
  flagName="$1"

  if [ -z "$flagName" ]; then
    main::end_script 1 "Cannot delete a flag without the name!"
  fi

  client::__send DELETE "/$flagName"
}

function client::update_flag() {
  flagName="$1"
  expression="$2"

  if [ -z "$expression" ] || [ -z "$flagName" ]; then
    main::end_script 1 "Cannot update a flag without the name and expression!"
  fi

  client::__send POST "/$flagName" "$expression" "Content-Type: application/json"
}

function client::__send() {
  method="$1"
  endpoint="$2"
  expression="${3:-}"
  headers="${4:-Content-Type: application/json}"

  if [ -z "$method" ] || [ -z "$endpoint" ]; then
    main::end_script 0 "Cannot make request without the HTTP method or endpoint!"
  fi

  if [ -n "$expression" ]; then
    data="{ \"expression\": \"$expression\" }"
  else
    data=""
  fi

  # echo
  # echo "client::__send()"
  # echo "    method: $method"
  # echo "    endpoint: $endpoint"
  # echo "    data: $data"
  # echo "    headers: $headers"
  # echo

  curl --request $method \
      --header "$headers" \
      --data "$data" \
      http://localhost:$PORT/flags$endpoint

  send_status="$?"

  echo

  return $send_status
}

########################
#                      #
# Script Functionality #
#                      #
########################

function main::end_script() {
  exit_code="${1:-0}"
  exit_message="${2:-Shutting down integration tests...}"

  echo $exit_message

  server::shutdown_roxy

  exit $exit_code
}

########
#      #
# Main #
#      #
########

function main() {
  server::start_roxy
  
  if ! server::await_roxy; then
    main::end_script 1 "Failed to access Roxy server, exiting!"
  fi

  echo "Cleaning up existing flags..."
  client::delete_all_flags

  echo "Creating integration test flags..."

  declare -a flag_names=(
    "dev.openfeature.bool_flag"
    "dev.openfeature.string_flag"
    "dev.openfeature.int_flag"
    "dev.openfeature.float_flag"
    "dev.openfeature.object_flag"
  )
  declare -a flag_expressions=(
    "true"
    "'"string-value"'"
    "42"
    "3.14"
    "'{"name":"OpenFeature","version":"1.0.0"}'"
  )

  names_length=${#flag_names[@]}
  expressions_length=${#flag_expressions[@]}

  if [ $names_length -ne $expressions_length ]; then
    main::end_script 1 "Non-matching array lengths"
  fi

  ## now loop through the above array
  for (( i=0; i<${names_length}; i++ ));
  do
    echo "Seeding ${flag_names[$i]}..."
    client::update_flag "${flag_names[$i]}" "${flag_expressions[$i]}"
    echo
  done

  echo "Integration test suite ready!"
}

if [[ "$1" == "stop" ]]; then
  main::end_script 0 "Closing existing services..."
fi

main "$@"