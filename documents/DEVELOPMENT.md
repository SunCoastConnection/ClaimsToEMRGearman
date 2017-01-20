# Development Environment

## Installing the Environment

This project uses a Homestead Vagrant virtual machine to rapidly create
environments for development and testing.

### Prerequisites

It's required to have the following installed and configured to run:

- [**Composer**](getcomposer.org): Standard PHP dependency management
    - Requires: [**PHP 5.6.0+**](php.net) and [**GIT**](git-scm.com)
- [**Vagrant**](vagrantup.com): Virtual Machine configuration management
    - Requires: [**parallels**](parallels.com), [**vmware**](vmware.com), or
[**virtualbox**](virtualbox.org)

**NOTE**: All `vagrant` commands are run from your host system in the projects
base directory.

### Initializing the Environment

From within the projects base directory.

Install the project dependencies:
```bash
$ composer install
```

Create a copy of the `Homestead` configuration:
```bash
$ cp utilities/vagrant/Homestead.yaml.example utilities/vagrant/Homestead.yaml
```

Configure the `Homestead` settings (ex: `provider`, `cpus`, `memory`, `ip`) to
your preferences:
```yaml
---
ip: "192.168.153.164"
memory: 2048
cpus: 1
hostname: ClaimsToEMRGearman
name: ClaimsToEMRGearman
provider: virtualbox
```

## Provisioning the Environment

Startup the development environment:
```bash
$ vagrant up
```

## Entering the Environment

Remote into the development environment:
```bash
$ vagrant ssh
```

## Exiting the Environemnt

Leave the development environment back to your host system:
```bash
$ exit
```

## Suspending Environment

Suspend the virtual machine for later use from your host system:
```bash
$ vagrant suspend
```

## Resuming Suspended Environment

Resuming the virtual machine from your host system:
```bash
$ vagrant up
```

## Destroying Environment

Destroy the virtual machine from your host system:
```bash
$ vagrant destroy
```

## Running the Unit Tests

Run the unit tests from within the virtual machine
```bash
$ cd ClaimsToEMRGearman
$ ./utilities/phpunit.sh
```

View the code compleation [report in your browser](http://claimstooemrgearman.homestead/).