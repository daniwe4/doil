#!/bin/bash

## Take in a username for the usermod
read -p "Please type in a username: " username
read -p "Your username is $username. Is this correct? [Type YES in uppercase] " confirmation

if [ "$confirmation" != "YES" ]
then
	exit 1
fi

# Brief user
read -p "You will need to enter your password for sudo in order to add the group docker and add your user to the group. Did you understand? [Type YES in uppercase] " understood
if [ "$understood" != "YES" ]
then
	exit 1
fi

# add group and add user to the group
sudo groupadd docker
sudo usermod -aG docker $username

echo "Done."
echo "You may reboot your system now."
