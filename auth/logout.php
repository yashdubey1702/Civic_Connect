<?php

session_start();
session_destroy();
header("Location: /town_issues/public/index.html");
exit;

