<?php
	
	// Include Utilities PHP File
	require_once('_utilities.php');
	
	
	// Initialize Error Array
	$errors = array();
	
	
	// Start Session
	session_start();
	
	
	// Connect To Database
	$pdo = dbConnect();
	
	
	// Require Login
	requireLogin();
	
	
	// Retrieve Current List of Courses
	$query = "
		SELECT DISTINCT
			`Course`.`ID` AS ID,
			`Course`.`name`,
			`Course`.`description`,
			`Course`.`prefix`,
			`Course`.`number`,
			GROUP_CONCAT(CONCAT(LEFT(`User`.`firstName`, 1), '. ', `User`.`lastName`) ORDER BY `User`.`lastName` SEPARATOR ', ') AS coordinators
		FROM `Course`
		LEFT JOIN `Coordinator`
		ON `Coordinator`.`fkCourse` = `Course`.`ID`
		LEFT JOIN `User`
		ON `User`.`ID` = `Coordinator`.`fkUser`
		WHERE
			`Course`.`status` = 1 AND
			`Course`.`fkDepartment` = :fkDepartment
		GROUP BY `Course`.`ID`
		ORDER BY `Course`.`prefix`, `Course`.`number`
	";
	$stmt = $pdo->prepare($query);
	$stmt->bindValue(':fkDepartment', $_SESSION['fkDepartment']);
	$success = $stmt->execute();
	if (!$success || ($courses = $stmt->fetchAll(PDO::FETCH_ASSOC)) === FALSE)
	{
		redirect('index.php');
	}
	
?>




<!DOCTYPE html>
<html lang="en">
	<head>
		<!-- Title and Icon -->
		<title>eAssess CCU - View Courses</title>
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
				<h2 class="title">View Courses</h2>
					
				<table class="verticalTable widthFull padded">
					<tr>
						<th>Code</th>
						<th>Name</th>
						<th>Coordinators</th>
						<th>Description</th>
						<th>Create Syllabus</th>
					</tr>
					
					<?php
						foreach ($courses as $course)
						{
					?>
					<tr class="mouseHover">
						<td class="clickable" onclick="window.location='courseView.php?idCourse=<?php echo $course['ID']; ?>'"><?php echo $course['prefix'] . ' ' . $course['number']; ?></td>
						<td class="center clickable" onclick="window.location='courseView.php?idCourse=<?php echo $course['ID']; ?>'"><?php echo $course['name']; ?></td>
						<td class="center clickable" onclick="window.location='courseView.php?idCourse=<?php echo $course['ID']; ?>'"><?php echo $course['coordinators']; ?></td>
						<td class="clickable" onclick="window.location='courseView.php?idCourse=<?php echo $course['ID']; ?>'"><?php echo $course['description']; ?></td>
						<td class="center"><a href="syllabusExport.php?course=<?php echo $course['ID']; ?>"><img title="Create Syllabus for <?php echo $course['prefix'] . ' ' . $course['number']; ?>" src="media/createSyllabus.png" /></a></td>
					</tr>
					<?php
						}
						unset($course);
					?>
					
				</table>
			</section>
			
			<?php require('html/footer.php'); ?>
		</div>
	</body>
</html>