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
ALTER TABLE `deepLearningUnitBlock` CHANGE `type` `type` ENUM('Main','Sidebar') NOT NULL DEFAULT 'Main';end
ALTER TABLE `deepLearningExperienceTrip` ADD `deepLearningEventDateID` INT(12) UNSIGNED ZEROFILL NULL AFTER `deepLearningExperienceID`;end
ALTER TABLE `deepLearningUnitBlock` DROP `deepLearningEventDateID`;end
";

// v0.0.03
$sql[$count][0] = "0.0.03";
$sql[$count][1] = "ALTER TABLE `deepLearningExperienceTrip` DROP `deepLearningEventDateID`;end
ALTER TABLE `deepLearningExperienceTrip` ADD `deepLearningEventDateIDList` VARCHAR(255) NULL AFTER `deepLearningExperienceID`;end
ALTER TABLE `deepLearningExperienceTrip` ADD UNIQUE(`deepLearningExperienceID`, `tripPlannerRequestID`);end
";
