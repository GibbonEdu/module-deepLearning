<?php
// USE ;end TO SEPARATE SQL STATEMENTS. DON'T USE ;end IN ANY OTHER PLACES!

$sql = [];
$count = 0;

// v0.1.00
$sql[$count][0] = "0.1.00";
$sql[$count][1] = "";

// v0.1.01
$count++;
$sql[$count][0] = "0.1.01";
$sql[$count][1] = "
INSERT INTO gibbonAction SET gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name='Deep Learning'), name='Manage Events', precedence=0, category='Admin', description='Allows the user to manage Deep Learning events.', URLList='events_manage.php,events_manage_add.php,events_manage_edit.php,events_manage_delete.php', entryURL='events_manage.php', entrySidebar='Y', menuShow='Y', defaultPermissionAdmin= 'Y', defaultPermissionTeacher='N', defaultPermissionStudent='N', defaultPermissionParent='N', defaultPermissionSupport='N', categoryPermissionStaff='Y', categoryPermissionStudent='N', categoryPermissionParent='N', categoryPermissionOther='N';end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , '1', (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Deep Learning' AND gibbonAction.name='Manage Events'));end
";

// v0.2.00
$sql[$count][0] = "0.2.00";
$sql[$count][1] = "
ALTER TABLE deepLearningEvent MODIFY COLUMN active enum('Y','N') DEFAULT 'Y';end
";
