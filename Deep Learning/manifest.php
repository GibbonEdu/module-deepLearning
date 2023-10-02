<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http:// www.gnu.org/licenses/>.
*/

// Basic variables
$name        = 'Deep Learning';
$description = "Enables schools to implement ICHK's Deep Learning approach, in which students choose learning experiences within a multi-day event.";
$entryURL    = "view.php";
$type        = "Additional";
$category    = 'Learn';
$version     = '0.0.01';
$author      = 'Sandra Kuipers';
$url         = 'https://github.com/GibbonEdu';

// Module tables & gibbonSettings entries
$moduleTables[] = "CREATE TABLE `deepLearningEvent` (
    `deepLearningEventID` INT(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT,
    `gibbonSchoolYearID` INT(3) UNSIGNED ZEROFILL NOT NULL,
    `name` VARCHAR(90) NOT NULL,
    `description` TEXT NULL,
    `active` ENUM('Y','N') NOT NULL DEFAULT 'Y',
    `backgroundImage` TEXT NULL,
    `gibbonYearGroupIDList` VARCHAR(255) NULL, 
    PRIMARY KEY (`deepLearningEventID`),
    UNIQUE KEY (`name`, `gibbonSchoolYearID`)
) ENGINE=InnoDB";

$moduleTables[] = "CREATE TABLE `deepLearningEventDate` (
    `deepLearningEventDateID` INT(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT,
    `deepLearningEventID` INT(10) UNSIGNED ZEROFILL NOT NULL,
    `eventDate` DATE NOT NULL,
    `name` VARCHAR(60) NOT NULL,
    `timeStart` TIME NULL,
    `timeEnd` TIME NULL,
    PRIMARY KEY (`deepLearningEventDateID`),
    UNIQUE KEY (`eventDate`, `deepLearningEventID`)
) ENGINE=InnoDB";

$moduleTables[] = "CREATE TABLE `deepLearningExperience` (
    `deepLearningExperienceID` INT(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT,
    `deepLearningEventID` INT(10) UNSIGNED ZEROFILL NOT NULL,
    `deepLearningExperienceIDOrigin` INT(10) UNSIGNED ZEROFILL NULL,
    `tripPlannerRequestID` INT(7) UNSIGNED ZEROFILL NULL,
    `name` VARCHAR(90) NOT NULL,
    `description` TEXT NULL,
    `status` ENUM('Draft','Published') NOT NULL DEFAULT 'Draft',
    `tags` TEXT NULL,
    `headerImage` VARCHAR(255) NULL,
    `minEnrollment` INT(3) NOT NULL,
    `maxEnrollment` INT(3) NOT NULL,
    `sequenceNumber` INT(6) NOT NULL,
    `gibbonYearGroupIDList` VARCHAR(255) NULL,
    `timestampCreated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `timestampModified` TIMESTAMP NULL,
    `gibbonPersonIDCreated` INT(10) UNSIGNED ZEROFILL NOT NULL,
    `gibbonPersonIDModified` INT(10) UNSIGNED ZEROFILL NOT NULL,
    PRIMARY KEY (`deepLearningExperienceID`),
    UNIQUE KEY (`name`, `deepLearningEventID`)
) ENGINE=InnoDB";

$moduleTables[] = "CREATE TABLE `deepLearningExperienceVenue` (
    `deepLearningExperienceVenueID` INT(12) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT,
    `deepLearningExperienceID` INT(10) UNSIGNED ZEROFILL NOT NULL,
    `deepLearningEventDateID` INT(10) UNSIGNED ZEROFILL NOT NULL,
    `gibbonSpaceID` INT(10) UNSIGNED ZEROFILL DEFAULT NULL,
    `venueExternal` VARCHAR(255) NOT NULL,
    `timeStart` TIME NULL,
    `timeEnd` TIME NULL,
    PRIMARY KEY (`deepLearningExperienceVenueID`)
) ENGINE=InnoDB";

$moduleTables[] = "CREATE TABLE `deepLearningExperienceBlock` (
    `deepLearningExperienceBlockID` INT(12) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT,
    `deepLearningExperienceID` INT(10) UNSIGNED ZEROFILL NOT NULL,
    `deepLearningEventDateID` INT(10) UNSIGNED ZEROFILL NULL,
    `title` VARCHAR(120) NOT NULL,
    `type` ENUM('Text','Photo','Video','Location') NOT NULL DEFAULT 'Text',
    `length` VARCHAR(3) NULL,
    `content` TEXT NULL,
    `sequenceNumber` INT(6) NOT NULL,
    PRIMARY KEY (`deepLearningExperienceBlockID`)
) ENGINE=InnoDB";

$moduleTables[] = "CREATE TABLE `deepLearningExperiencePhoto` (
    `deepLearningExperiencePhotoID` INT(12) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT,
    `deepLearningExperienceID` INT(10) UNSIGNED ZEROFILL NOT NULL,
    `filePath` VARCHAR(255) NOT NULL,
    `caption` VARCHAR(120) NULL,
    `sequenceNumber` INT(6) NOT NULL,
    PRIMARY KEY (`deepLearningExperiencePhotoID`)
) ENGINE=InnoDB";

$moduleTables[] = "CREATE TABLE `deepLearningStaff` (
    `deepLearningStaffID` INT(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT,
    `deepLearningExperienceID` INT(10) UNSIGNED ZEROFILL NOT NULL,
    `deepLearningEventDateID` INT(10) UNSIGNED ZEROFILL NULL,
    `gibbonPersonID` INT(10) UNSIGNED ZEROFILL NOT NULL,
    `tripLeader` ENUM('Y','N') NOT NULL DEFAULT 'N', 
    `role` VARCHAR(60) NOT NULL,
    PRIMARY KEY (`deepLearningStaffID`),
    UNIQUE KEY (`gibbonPersonID`, `deepLearningExperienceID`)
) ENGINE=InnoDB";

$moduleTables[] = "CREATE TABLE `deepLearningEnrolment` (
    `deepLearningEnrolmentID` INT(12) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT,
    `deepLearningExperienceID` INT(10) UNSIGNED ZEROFILL NOT NULL,
    `deepLearningSignUpID` INT(12) UNSIGNED DEFAULT NULL,
    `gibbonPersonID` INT(10) UNSIGNED ZEROFILL NOT NULL,
    `status` ENUM('Pending','Confirmed') NOT NULL DEFAULT 'Pending',
    `notes` TEXT NULL,
    `timestampCreated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `gibbonPersonIDCreated` INT(10) UNSIGNED ZEROFILL NOT NULL,
    PRIMARY KEY (`deepLearningEnrolmentID`),
    UNIQUE KEY (`gibbonPersonID`, `deepLearningExperienceID`)
) ENGINE=InnoDB;";

$moduleTables[] = "CREATE TABLE `deepLearningSignUp` (
    `deepLearningSignUpID` INT(12) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT,
    `deepLearningExperienceID` INT(10) UNSIGNED ZEROFILL NOT NULL,
    `gibbonPersonID` INT(10) UNSIGNED ZEROFILL NOT NULL,
    `choice` ENUM('1','2','3') NOT NULL DEFAULT '1',
    `timestampCreated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `gibbonPersonIDCreated` INT(10) UNSIGNED ZEROFILL NOT NULL,
    PRIMARY KEY (`deepLearningSignUpID`),
    UNIQUE KEY (`gibbonPersonID`, `choice`, `deepLearningExperienceID`)
) ENGINE=InnoDB;";

// Add gibbonSettings entries
$gibbonSetting[] = "INSERT INTO `gibbonSetting` (`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES ('Deep Learning', 'welcomeText', 'Welcome Text', '', '')";

// Action rows
// One array per action

$actionRows[] = [
    'name'                      => 'Manage Events',
    'precedence'                => '0',
    'category'                  => 'Administration',
    'description'               => 'Allows the user to manage Deep Learning events.',
    'URLList'                   => 'events_manage.php,events_manage_add.php,events_manage_edit.php,events_manage_delete.php',
    'entryURL'                  => 'events_manage.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionTeacher'  => 'N',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'N',
];
 
$actionRows[] = [
    'name'                      => 'Manage Experiences_all',
    'precedence'                => '1',
    'category'                  => 'Administration',
    'description'               => 'Allows the user to manage all Deep Learning experiences.',
    'URLList'                   => 'experience_manage.php,experience_manage_add.php,experience_manage_delete.php,experience_manage_edit.php',
    'entryURL'                  => 'experience_manage.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionTeacher'  => 'N',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'N',
];

$actionRows[] = [
    'name'                      => 'Manage Experiences_my',
    'precedence'                => '0',
    'category'                  => 'Administration',
    'description'               => 'Allows the user to manage Deep Learning experiences they have edit access to.',
    'URLList'                   => 'experience_manage.php,experience_manage_add.php,experience_manage_delete.php,experience_manage_edit.php',
    'entryURL'                  => 'experience_manage.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'N',
    'defaultPermissionTeacher'  => 'Y',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'N',
];

$actionRows[] = [
    'name'                      => 'Manage Sign Up',
    'precedence'                => '0',
    'category'                  => 'Administration',
    'description'               => 'Manage sign ups and enrolments for Deep Learning experiences.',
    'URLList'                   => 'signUp_manage.php,signUp_manage_generate.php',
    'entryURL'                  => 'signUp_manage.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionTeacher'  => 'N',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'N',
];

$actionRows[] = [
    'name'                      => 'Settings',
    'precedence'                => '0',
    'category'                  => 'Administration',
    'description'               => 'Allows the user change Deep Learning settings.',
    'URLList'                   => 'settings.php',
    'entryURL'                  => 'settings.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionTeacher'  => 'N',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'N',
];

$actionRows[] = [
    'name'                      => 'Deep Learning Events_view',
    'precedence'                => '0',
    'category'                  => 'Discover',
    'description'               => 'Enables users to view active Deep Learning events.',
    'URLList'                   => 'view.php,view_experience.php',
    'entryURL'                  => 'view.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionTeacher'  => 'Y',
    'defaultPermissionStudent'  => 'Y',
    'defaultPermissionParent'   => 'Y',
    'defaultPermissionSupport'  => 'Y',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'Y',
    'categoryPermissionParent'  => 'Y',
    'categoryPermissionOther'   => 'Y',
];

$actionRows[] = [
    'name'                      => 'Deep Learning Events_viewInactive',
    'precedence'                => '1',
    'category'                  => 'Discover',
    'description'               => 'Enables users to view active and inactive Deep Learning events.',
    'URLList'                   => 'view.php,view_experience.php',
    'entryURL'                  => 'view.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionTeacher'  => 'Y',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'N',
];

$actionRows[] = [
    'name'                      => 'Deep Learning Events_signUp',
    'precedence'                => '2',
    'category'                  => 'Discover',
    'description'               => 'Enables users to view and sign up for active Deep Learning events.',
    'URLList'                   => 'view.php,view_experience.php,view_experience_signUp.php',
    'entryURL'                  => 'view.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'N',
    'defaultPermissionTeacher'  => 'N',
    'defaultPermissionStudent'  => 'Y',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'Y',
    'categoryPermissionParent'  => 'Y',
    'categoryPermissionOther'   => 'Y',
];

$actionRows[] = [
    'name'                      => 'My Deep Learning',
    'precedence'                => '0',
    'category'                  => 'Discover',
    'description'               => 'Enables users to view their enrolled Deep Learning events.',
    'URLList'                   => 'viewMyDL.php',
    'entryURL'                  => 'viewMyDL.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionTeacher'  => 'Y',
    'defaultPermissionStudent'  => 'Y',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'Y',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'N',
];

$actionRows[] = [
    'name'                      => 'Deep Learning Overview',
    'precedence'                => '0',
    'category'                  => 'Reports',
    'description'               => 'View an overview of all active Deep Learning experiences.',
    'URLList'                   => 'report_overview.php',
    'entryURL'                  => 'report_overview.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionTeacher'  => 'N',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'N',
];

$actionRows[] = [
    'name'                      => 'Deep Learning Groups',
    'precedence'                => '0',
    'category'                  => 'Reports',
    'description'               => 'View students enrolled in all active Deep Learning experiences.',
    'URLList'                   => 'report_groups.php',
    'entryURL'                  => 'report_groups.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionTeacher'  => 'N',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'N',
];

$actionRows[] = [
    'name'                      => 'Students Not Signed Up',
    'precedence'                => '0',
    'category'                  => 'Reports',
    'description'               => 'View a report of students not signed up for an event.',
    'URLList'                   => 'report_notSignedUp.php',
    'entryURL'                  => 'report_notSignedUp.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionTeacher'  => 'N',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'N',
];

$actionRows[] = [
    'name'                      => 'Students Not Enrolled',
    'precedence'                => '0',
    'category'                  => 'Reports',
    'description'               => 'View a report of students who have signed up but have not been enrolled in an experience.',
    'URLList'                   => 'report_notEnrolled.php',
    'entryURL'                  => 'report_notEnrolled.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionTeacher'  => 'N',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'N',
];

$actionRows[] = [
    'name'                      => 'View DL Staffing',
    'precedence'                => '0',
    'category'                  => 'Reports',
    'description'               => 'View a report of staff assigned to an experience.',
    'URLList'                   => 'report_staffing.php',
    'entryURL'                  => 'report_staffing.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionTeacher'  => 'N',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'N',
];

$actionRows[] = [
    'name'                      => 'View Unassigned Staff',
    'precedence'                => '0',
    'category'                  => 'Reports',
    'description'               => 'View a report of staff not assigned to an experience.',
    'URLList'                   => 'report_unassigned.php',
    'entryURL'                  => 'report_unassigned.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionTeacher'  => 'N',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'N',
];


// Hooks
// $hooks[] = ''; // Serialised array to create hook and set options. See Hooks documentation online.
