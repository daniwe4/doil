#!/usr/bin/env bash

source ${SCRIPT_DIR}/updates/update.sh

doil_update_20230817() {
  update
  doil_system_install_httpserver
  return $?
}