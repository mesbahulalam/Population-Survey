<?php
// config.php
$db = new PDO('mysql:host=localhost;dbname=population_survey', 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
