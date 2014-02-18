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
			`ID`,
			`name`,
			`description`,
			`prefix`,
			`number`
		FROM `Course`
		WHERE
			`ID` = :ID AND
			`fkDepartment` = :fkDepartment AND
			`status` = 1
		LIMIT 1
	";
	$stmt = $pdo->prepare($query);
	$stmt->bindValue(':ID', parseInt($_REQUEST['idCourse']));
	$stmt->bindValue(':fkDepartment', $_SESSION['fkDepartment']);
	$success = $stmt->execute();
	if (!$success || ($courseInfo = $stmt->fetch(PDO::FETCH_ASSOC)) === FALSE)
	{
		redirect('coursesView.php');
	}
	
	
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
	if (!$success || ($objectives = $stmt->fetchAll(PDO::FETCH_ASSOC)) === FALSE)
	{
		redirect('coursesView.php');
	}
	
?>




<!DOCTYPE html>
<html lang="en">
	<head>
		<!-- Title and Icon -->
		<title>eAssess CCU - View Course <?php echo $courseInfo['prefix'] . ' ' . $courseInfo['number']; ?></title>
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
				<h2 class="title">View Course <?php echo $courseInfo['prefix'] . ' ' . $courseInfo['number']; ?></h2>
				
				<h3>Class Information</h3>
				<table class="horizontalTable padded widthFull borderRows">
					<tr>
						<th>Class</th>
						<td><?php echo $courseInfo['prefix'] . ' ' . $courseInfo['number']; ?></td>
					</tr>
					<tr>
						<th>Name</th>
						<td><?php echo $courseInfo['number']; ?></td>
					</tr>
					<tr>
						<th>Description</th>
						<td><?php echo $courseInfo['description']; ?></td>
					</tr>
				</table>
				
				<h3>Class Objectives</h3>
				<table class="verticalTable padded widthFull">
					<tr>
						<th>Number</th>
						<th>Description</th>
						<th>SLOs</th>
					</tr>
					
					<?php
						foreach ($objectives as $objective)
						{
					?>
					<tr>
						<td class="center"><?php echo $objective['number']; ?></td>
						<td><?php echo $objective['description']; ?></td>
						<td class="center"><?php echo $objective['SLOs']; ?></td>
					</tr>
					<?php
						}
						unset($objective);
						
						if (count($objectives) == 0)
						{
							echo '<td colspan="3" class="center">This class does not have any objectives.</td>';
						}
					?>
					
				</table>
				
				<a href="coursesView.php" class="button">Done</a>
			</section>
			
			<?php require('html/footer.php'); ?>
		</div>
	</body>
</html>