#!/usr/bin/env bash

SCRIPT_DIR=$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )
source ${SCRIPT_DIR}/../lib/include/system.sh
source ${SCRIPT_DIR}/../lib/include/log.sh
source ${SCRIPT_DIR}/colors.sh

doil_update_20220604() {

  doil_status_send_message "Removing old doil system"
  doil_system_remove_old_version
  doil_status_okay

  doil_status_send_message "Copying doil new system"
  doil_system_create_folder
  doil_system_copy_doil
  doil_status_okay

  doil_status_send_message "Generating new log files"
  doil_system_setup_log
  doil_status_okay

	return 0
}