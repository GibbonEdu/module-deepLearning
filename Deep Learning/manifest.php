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

// This file describes the module, including database tables

// Basic variables
$name        = 'Deep Learning';            // The name of the module as it appears to users. Needs to be unique to installation. Also the name of the folder that holds the unit.
$description = 'Allows schools to implement ICHK\'s Deep Learning curriculum, in which students choose learning experiences within an event. ';            // Short text description
$entryURL    = "index.php";   // The landing page for the unit, used in the main menu
$type        = "Additional";  // Do not change.
$category    = 'Learn';            // The main menu area to place the module in
$version     = '0.1.00';            // Version number
$author      = 'Harry Merrett';            // Your name
$url         = '';            // Your URL

// Module tables & gibbonSettings entries
$moduleTables[] = "CREATE TABLE `deepLearningEvent` (
  `deepLearningEventID` int(10) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(40) NOT NULL,
  `description` TEXT NULL,
  `backgroundImage` text,
  `active` boolean,
  PRIMARY KEY (`deepLearningEventID`)
)"; // One array entry for every database table you need to create. Might be nice to preface the table name with the module name, to keep the db neat.
$moduleTables[] = "CREATE TABLE `deepLearningExperience` (
  `deepLearningExperienceID` int(10) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `deepLearningEventID` INT(10) UNSIGNED ZEROFILL NOT NULL,
  `deepLearningMajorID1` INT(10) UNSIGNED ZEROFILL NOT NULL,
  `deepLearningMajorID2` INT(10) UNSIGNED ZEROFILL NULL,
  `minor1` varchar(30),
  `minor2` varchar(30),
  `headerImage` text,
  `maxEnrollment` int(3),
  `blurb` text,
  `timestamp` timestamp,
  PRIMARY KEY (`deepLearningExperienceID`)
)"; // Also can be used to put data into gibbonSettings. Other sql can be run, but resulting data will not be cleaned up on uninstall.
$moduleTables[] = "CREATE TABLE `deepLearningDate` (
  `deepLearningDateID` int(10) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `deepLearningEventID` int(10) unsigned,
  `date` date,
  `name` varchar(30),
  PRIMARY KEY (`deepLearningDateID`)
)";
$moduleTables[] = "CREATE TABLE `deepLearningMajor` (
  `deepLearningMajorID` int(10) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `name` varchar(30),
  PRIMARY KEY (`deepLearningMajorID`)
)";
$moduleTables[] = "CREATE TABLE `deepLearningExperienceHost` (
  `deepLearningExperienceHostID` int(10) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `gibbonPersonID` int(10) unsigned,
  `deepLearningExperienceID` int(10) unsigned,
  PRIMARY KEY (`deepLearningExperienceHostID`)
)";
$moduleTables[] = "CREATE TABLE `deepLearningEnrollment` (
  `deepLearningEnrollemtnID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `deepLearningExperienceID` int(10) unsigned DEFAULT NULL,
  `gibbonPersonID` int(10) unsigned zerofill DEFAULT NULL,
  `enrolDate` date DEFAULT NULL,
  `choice` enum('1','2','3') NOT NULL DEFAULT '1',
  `status` enum('Pending','Accepted') NOT NULL DEFAULT 'Pending',
  PRIMARY KEY (`deepLearningEnrollemtnID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
$moduleTables[] = "CREATE TABLE `deepLearningExperienceBlock` (
  `deepLearningExperienceBlockID` int(10) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `deepLearningExperienceID` int(10) unsigned zerofill NOT NULL,
  `title` varchar(100) NOT NULL,
  `type` varchar(50) NOT NULL,
  `length` varchar(3) NULL DEFAULT NULL,
  `contents` text NOT NULL,
  PRIMARY KEY (`deepLearningExperienceBlockID`)
)";

// Add gibbonSettings entries
// $gibbonSetting[] = "";

// Action rows
// One array per action
$actionRows[] = [
    'name'                      => 'Manage Majors', // The name of the action (appears to user in the right hand side module menu)
    'precedence'                => '0',// If it is a grouped action, the precedence controls which is highest action in group
    'category'                  => 'Admin', // Optional: subgroups for the right hand side module menu
    'description'               => 'Allows the user to manage experience majors.', // Text description
    'URLList'                   => 'majors_manage.php,majors_manage_add.php,majors_manage_delete.php,majors_manage_edit.php', // List of pages included in this action
    'entryURL'                  => 'majors_manage.php', // The landing action for the page.
    'entrySidebar'              => 'Y', // Whether or not there's a sidebar on entry to the action
    'menuShow'                  => 'Y', // Whether or not this action shows up in menus or if it's hidden
    'defaultPermissionAdmin'    => 'Y', // Default permission for built in role Admin
    'defaultPermissionTeacher'  => 'N', // Default permission for built in role Teacher
    'defaultPermissionStudent'  => 'N', // Default permission for built in role Student
    'defaultPermissionParent'   => 'N', // Default permission for built in role Parent
    'defaultPermissionSupport'  => 'N', // Default permission for built in role Support
    'categoryPermissionStaff'   => 'Y', // Should this action be available to user roles in the Staff category?
    'categoryPermissionStudent' => 'N', // Should this action be available to user roles in the Student category?
    'categoryPermissionParent'  => 'N', // Should this action be available to user roles in the Parent category?
    'categoryPermissionOther'   => 'N', // Should this action be available to user roles in the Other category?
];

// Hooks
// $hooks[] = ''; // Serialised array to create hook and set options. See Hooks documentation online.
