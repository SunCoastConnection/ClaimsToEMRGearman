<?php

use \Symfony\Component\Console\Application;
use \SunCoastConnection\ClaimsToEMRGearman\Console\Available;
use \SunCoastConnection\ClaimsToEMRGearman\Console\Register;

require __DIR__.'/../vendor/autoload.php';

$application = new Application();

$application->add(new Available);
$application->add(new Register);

$application->run();