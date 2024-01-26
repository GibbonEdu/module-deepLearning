<?php
// USE ;end TO SEPARATE SQL STATEMENTS. DON'T USE ;end IN ANY OTHER PLACES!

$sql = [];
$count = 0;

// v0.0.01
$sql[$count][0] = "0.0.01";
$sql[$count][1] = "";

// v0.0.02
$sql[$count][0] = "0.0.02";
$sql[$count][1] = "ALTER TABLE `deepLearningUnit` ADD `enrolmentMin` INT(3) NOT NULL AFTER `minors`;end
ALTER TABLE `deepLearningUnit` ADD `enrolmentMax` INT(3) NOT NULL AFTER `enrolmentMin`;end
ALTER TABLE `deepLearningExperience` DROP `cost`, DROP `location`, DROP `provider`;end
ALTER TABLE `deepLearningExperience` DROP `enrolmentMin`, DROP `enrolmentMax`;end
";
