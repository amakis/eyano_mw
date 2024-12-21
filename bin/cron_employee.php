<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Application\Actions\Attendance\ListAttendanceAction;

$att = new ListAttendanceAction;
$att->action();