# Claims To EMR Gearman

## Gearman Worker Command Script

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
$ ./gearman-worker register  --comfig=/path/to/config.php ClaimsToEMR.Credentials.Lookup
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
