#!/bin/bash

# doil is a tool that creates and manages multiple docker container
# with ILIAS and comes with several tools to help manage everything.
# It is able to download ILIAS and other ILIAS related software
# like cate.
#
# Copyright (C) 2020 - 2021 Laura Herzog (laura.herzog@concepts-and-training.de)
# Permission to copy and modify is granted under the AGPL license
#
# Contribute: https://github.com/conceptsandtraining/doil
#
# /ᐠ｡‸｡ᐟ\
# Thanks to Concepts and Training for supporting doil

cat <<-EOF
NAME
  doil system:user - manages the doil users

SYNOPSIS
  doil system:user [command]

DESCRIPTION
  This command manages the users which are registered
  at the doil system. Only they are able to use the full
  functionality of doil.

  Deleting a user will keep their installed instances.

  This command only works for sudo users.

EXAMPLE:
  doil system:user add john

COMMANDS
  add    adds a user to the doil system
  delete removes a user from the doil system
  list   lists registered doil users

OPTIONS
  -q|--quiet no output will be displayed
EOF