# Claims To EMR Gearman

## Gearman Worker Command Script

[Gearman](http://gearman.org/) provides a generic application framework to farm
out work to other machines or processes that are better suited to do the work.
It allows you to do work in parallel, to load balance processing, and to call
functions between languages.

Gearman consists of three parts:

- **Client**: Creating a job to be run and sending it to a job server
- **Job Server**: Find a suitable worker that can run the job to forward the job
- **Worker**: Perform the requested work by the client and sends a response to
the client through the job server

Gearman provides client and worker APIs that your applications call to talk with
the Gearman job server, so you don’t need to deal with networking or mapping of
jobs.

Claims To EMR Gearman has a command line script to manage the workers.  The
script can list the workers and register them with the Gearman server.

To list the available workers run the following command:
```bash
$ ./gearman-worker available
Workers:
	ClaimsToEMR.Credentials.Lookup
	ClaimsToEMR.Credentials.Register
	ClaimsToEMR.Claims.Retrieve
	ClaimsToEMR.Claims.Process

```

To register workers with the Gearman server run the following command:
```bash
$ ./gearman-worker register ClaimsToEMR.Credentials.Lookup
```

The command line script can have a configuration set by providing the path:
```bash
$ ./gearman-worker register --comfig=/path/to/config.php ClaimsToEMR.Credentials.Lookup
```

If no configuration is provided the script will look for a file the same name as
the script with a `.php` extension; first in the current working directory, then
within a config directory within the working directory, next in the scripts
directory, and lastly in a config directory within the scripts directory.

Example:
    Script: /home/user/bin/gearman-worker
    Working Directory: /home/user

    1. /home/user/gearman-worker.php
    2. /home/user/config/gearman-worker.php
    3. /home/user/bin/gearman-worker.php
    4. /home/user/bin/config/gearman-worker.php

# Additional documentation:

- [INSTALL](./documents/INSTALL.md): Instructions for installing project
- [DEVELOPMEMT](./documents/DEVELOPMENT.md): Development information
