# Project Installation

## Prerequisites

It's required to have the following installed and configured to run:

- [**Composer**](getcomposer.org): Standard PHP dependency management
    - Requires: [**PHP 5.6.0+**](php.net) and [**GIT**](git-scm.com)
- [**GIT**](git-scm.com): DVCS (Distributed Version Control System) project
management software

## Install project

Install this project to the local system:
```bash
$ git clone git@github.com:SunCoastConnection/ClaimsToEMRGearman.git
```

Install the project dependencies:
```bash
$ composer install
```

## Supervisor

It's suggested (but optional) to have an application that manages Gearman
workers to handle starting, watching/re-spawning, and terminating the worker
processes. [Supervisor](http://supervisord.org/) provides the necessary feature
set to perform this task and is relatively simple to setup. It can be installed
with [`setuptools`](https://pypi.python.org/pypi/setuptools) (for distributing
Python packages).

### Installing Package

```bash
$ sudo easy_install supervisor
```

### Configure Supervisor

Once Supervisor is installed, you'll need to configure it to manage the Gearman
workers. The most sane way to manage workers is to provide a configuration file
per managed program.  Make sure the main configuration file (ie:
`/etc/supervisor/supervisord.conf`) has an `include` section with a `files`
directive pointing to a blob path, as follows:

```ini
[include]
files = /etc/supervisor/conf.d/*.conf
```

Add a configuration file for each worker (to the `conf.d` directory). Below is a
basic template for configuring a worker
(ex: `/etc/supervisor/conf.d/gearman-WORKER-NAME.conf`):

```ini
[program:gearman-WORKER-NAME]
directory=/PATH/TO/WORKING/DIRECTORY
command=/PATH/TO/gearman-worker register ClaimsToEMR.Lookup
autorestart=true
process_name=%(program_name)s-%(process_num)s
numprocs=10
```

Remember to provide the file a name relative to its task, change the
configuration section name to match, update the `directory` and `command` paths,
and change the remaining settings as needed.  You can locate additional
configuration options (ex: `priority`) in the Supervisor configuration
[documentation](http://supervisord.org/configuration.html#program-x-section-settings).

### Restarting Supervisor

After adding a new configuration file reload Supervisor:

```bash
$ sudo service supervisor force-reload
```
