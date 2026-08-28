<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('contributions:generate-reports')->monthlyOn(1, '00:00');
