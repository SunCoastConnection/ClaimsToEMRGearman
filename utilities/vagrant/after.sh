#!/usr/bin/env bash
#
# Author: Sam Likins <sam.likins@wsi-services.com>
# Copyright: Copyright (c) 2016, WSI-Services
#
# License: http://opensource.org/licenses/gpl-3.0.html
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <http://www.gnu.org/licenses/>.

# If you would like to do some extra provisioning you may
# add any commands you wish to this file and they will
# be run after the Homestead machine is provisioned.

echo "## Vagrant post install script..."

echo "## Resetting MySQL Password for user 'homestead' to 'secret'"
mysql -u homestead -psecret -e "SET PASSWORD=PASSWORD('secret');"

echo "## Enabling XDebug PHP Module"
sudo phpenmod xdebug

echo "## Refreshing the system packages"
sudo apt-get update
sudo apt-get upgrade

echo "## Installing dependent system packages"
sudo apt-get install -y gearman-job-server gearman-tools

echo "## Installing dependent Python packages"
sudo easy_install supervisor

echo "## Configuring Supervisor Gearman initialization settings"
sudo bash -c "cat > /etc/supervisor/conf.d/gearman-claimstoemr.conf" << EOL
[program:gearman-claimstoemr]
directory=/home/vagrant/ClaimsToEMR
command=/home/vagrant/ClaimsToEMR/gearman-workers
autorestart=true
process_name=%(program_name)s-%(process_num)s
numprocs=10
EOL

echo "## Restarting Supervisor service"
sudo service supervisor restart