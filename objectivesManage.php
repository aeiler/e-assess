<?php
	
	// Include Utilities PHP File
	require_once('_utilities.php');
	
	
	// Initialize Error Array
	$errors = array();
	
	
	// Start Session
	session_start();
	
	
	// Connect To Database
	$pdo = dbConnect();
	
	
	// Require User Login
	requireLogin();
	
	
	// Require Provided Course ID
	// 1. Attempt to Query Database for Supplied Course's Info
	$query = "
		SELECT
			`Course`.`ID`,
			`Course`.`name`,
			`Course`.`description`,
			`Course`.`prefix`,
			`Course`.`number`,
			IF(`Coordinator`.`fkUser` IS NULL, 0, 1) AS coordinator
		FROM `Course`
		LEFT JOIN `Coordinator`
		ON `Coordinator`.`fkCourse` = `Course`.`ID` AND `Coordinator`.`fkUser` = :fkUser
		WHERE `Course`.`ID` = :ID AND `Course`.`fkDepartment` = :fkDepartment AND `Course`.`status` = 1
		LIMIT 1
	";
	$stmt = $pdo->prepare($query);
	$stmt->bindValue(':ID', parseInt($_REQUEST['idCourse']));
	$stmt->bindValue(':fkUser', $_SESSION['ID']);
	$stmt->bindValue(':fkDepartment', $_SESSION['fkDepartment']);
	$success = $stmt->execute();
	
	// 2. Ensure a Valid Course Was Found in the Database
	if (!$success || ($courseInfo = $stmt->fetch(PDO::FETCH_ASSOC)) === FALSE) redirect('coursesManage.php');
	
	// 3. If Logged In User Is Faculty, Check For Coordinator Permissions
	if ($_SESSION['level'] == LEVEL_FACULTY && $courseInfo['coordinator'] === 0) redirect('coursesManage.php');
	
	
	// Retrieve Current List of Objectives
	$query = "
		SELECT DISTINCT
			`Objective`.`ID` AS ID,
			`Objective`.`description` AS description,
			`Objective`.`number`,
			`Objective`.`status` AS status,
			GROUP_CONCAT(`SLO`.`code` ORDER BY `SLO`.`code` SEPARATOR ', ') AS SLOs
		FROM `Objective`
		LEFT JOIN `SLOXObjective`
		ON
			`SLOXObjective`.`fkObjective` = `Objective`.`ID` AND
			`SLOXObjective`.`dateDisabled` IS NULL
		LEFT JOIN `SLO`
		ON
			`SLO`.`ID` = `SLOXObjective`.`fkSLO` AND
			`SLO`.`status` = 1
		WHERE
			`Objective`.`fkCourse` = :fkCourse
		GROUP BY `Objective`.`ID`
		ORDER BY `Objective`.`status` DESC, `Objective`.`number`
	";
	$stmt = $pdo->prepare($query);
	$stmt->bindValue(':fkCourse', $courseInfo['ID']);
	$success = $stmt->execute();
	
?>




<!DOCTYPE html>
<html lang="en">
	<head>
		<!-- Title and Icon -->
		<title>eAssess CCU - Manage Course Objectives for <?php echo $courseInfo['prefix'] . ' ' . $courseInfo['number']; ?></title>
		<link rel="icon" href="media/favicon.ico" />
		
		<!-- Meta Information -->
		<meta charset="utf-8" />
		
		<!-- Stylesheets -->
		<link rel="stylesheet" type="text/css" href="css/_reset.css" />
		<link rel="stylesheet" type="text/css" href="css/_globalStyles.css" />
	</head>
	
	<body>
		<?php require('html/header.php'); ?>
		
		<div id="siteContainer" class="pageWidth">
			<section class="bgField shadow corner center">
				<h2 class="title">Manage Course Objectives for <?php echo $courseInfo['prefix'] . ' ' . $courseInfo['number']; ?></h2>
				
				<table class="verticalTable padded widthFull">
					<tr>
						<th>Number</th>
						<th>Description</th>
						<th>SLOs</th>
						<th>Edit</th>
						<th>Disable/Enable</th>
					</tr>
					
					<?php
						while ($success && ($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== FALSE)
						{
					?>
					<tr>
						<td><?php echo $row['number']; ?></td>
						<td><?php echo $row['description']; ?></td>
						<td><?php echo $row['SLOs']; ?></td>
						
						<td class="center">
							<a href="objectiveEdit.php?idObjective=<?php echo $row['ID']; ?>"><img src="media/edit.png" title="Edit Objective <?php echo $row['number']; ?>" width="30" /></a>
						</td>
						<td class="center">
							<a href="objectiveStatus.php?idObjective=<?php echo $row['ID']; ?>">
								<?php echo ($row['status']) ? '<img src="media/plus.png" title="Disable Objective ' . $row['number'] . '" width="30" />' : '<img src="media/minus.png" title="Enable Objective ' . $row['number'] . '" width="30" />'; ?>
							</a>
						</td>
					</tr>
					<?php
						}
					?>
					
				</table>
				
				<a href="objectiveNew.php?idCourse=<?php echo $courseInfo['ID']; ?>" class="button">Create a New Objective</a>
			</section>
			
			<?php require('html/footer.php'); ?>
		</div>
	</body>
</html>