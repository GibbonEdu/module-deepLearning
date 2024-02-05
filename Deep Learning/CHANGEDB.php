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

// v0.0.04
$sql[$count][0] = "0.0.04";
$sql[$count][1] = "INSERT INTO `gibbonAction` (`gibbonActionID`, `gibbonModuleID`, `name`, `precedence`, `category`, `description`, `URLList`, `entryURL`, `entrySidebar`, `defaultPermissionAdmin`, `defaultPermissionTeacher`, `defaultPermissionStudent`, `defaultPermissionParent`, `defaultPermissionSupport`, `categoryPermissionStaff`, `categoryPermissionStudent`, `categoryPermissionParent`, `categoryPermissionOther`) VALUES (NULL, (SELECT gibbonModuleID FROM gibbonModule WHERE name='Deep Learning'), 'View Student Choices', 0, 'Reports', 'View a list of choices students have made for a DL event.', 'report_choices.php','report_choices.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'N');end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , '1', (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Deep Learning' AND gibbonAction.name='View Student Choices'));end
";

// v0.0.05
$sql[$count][0] = "0.0.05";
$sql[$count][1] = "INSERT INTO `gibbonAction` (`gibbonActionID`, `gibbonModuleID`, `name`, `precedence`, `category`, `description`, `URLList`, `entryURL`, `entrySidebar`, `defaultPermissionAdmin`, `defaultPermissionTeacher`, `defaultPermissionStudent`, `defaultPermissionParent`, `defaultPermissionSupport`, `categoryPermissionStaff`, `categoryPermissionStudent`, `categoryPermissionParent`, `categoryPermissionOther`) VALUES (NULL, (SELECT gibbonModuleID FROM gibbonModule WHERE name='Deep Learning'), 'View Deep Learning_myChildren', 0, 'Explore', 'Enables parents to view enrolled Deep Learning events for their children.', 'viewDL.php','viewDL.php', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'N', 'Y', 'N');end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , '004', (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Deep Learning' AND gibbonAction.name='View Deep Learning_myChildren'));end
";
