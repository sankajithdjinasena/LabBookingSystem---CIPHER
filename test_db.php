<?php
require 'includes/config.php';
require 'includes/functions.php';

$res = create_booking(4, 2, "meeting", "2026-12-12 10:00:00", "2026-12-12 12:00:00", 5, 11);
print_r($res);
