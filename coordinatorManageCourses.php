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
		SELECT
			`Course`.`ID`,
			`Course`.`name`,
			`Course`.`prefix`,
			`Course`.`number`,
			`Course`.`status`
		FROM `Course`
		LEFT JOIN `Coordinator`
		ON `Coordinator`.`fkCourse` = `Course`.`ID` AND `Coordinator`.`fkUser` = :fkUser
		WHERE
			`Course`.`fkDepartment` = :fkDepartment
			AND `Coordinator`.`fkCourse` IS NOT NULL
		ORDER BY `Course`.`status` DESC, `Course`.`prefix`, `Course`.`number`
	";
	$stmt = $pdo->prepare($query);
	$stmt->bindValue(':fkUser', $_SESSION['ID']);
	$stmt->bindValue(':fkDepartment', $_SESSION['fkDepartment']);
	$success = $stmt->execute();
	if (!$success || ($courses = $stmt->fetchAll(PDO::FETCH_ASSOC)) === FALSE) redirect('index.php');
	
	
?>




<!DOCTYPE html>
<html lang="en">
	<head>
		<!-- Title and Icon -->
		<title>eAssess CCU - Manage Coordinator Courses</title>
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
				<h2 class="title">Manage Coordinator Courses</h2>
				
				<table class="verticalTable padded widthFull">
					<tr>
						<th>Code</th>
						<th>Name</th>
						<th>Edit</th>
						<th>Manage Objectives</th>
					</tr>
					
					<?php
						foreach ($courses as $course)
						{
					?>
					<tr>
						<td><?php echo $course['prefix'] . ' ' . $course['number']; ?></td>
						<td><?php echo $course['name']; ?></td>
						<td class="center">
							<?php
								if ($course['status'])
								{
									echo '<a href="courseEdit.php?source=coordinatorManageCourses.php&idCourse=' .  $course['ID'] . '">';
									echo '<img src="media/edit.png" title="Edit ' . $course['prefix'] . ' ' . $course['number'] . '" width="30" />';
									echo '</a>';
								}
								else
								{
									echo '-';
								}
							?>
						</td>
						<td class="center">
							<?php
								if ($course['status']) 
								{
									echo '<a href="objectivesManage.php?source=coordinatorManageCourses.php&idCourse=' .  $course['ID'] . '">';
									echo '<img src="media/manage.png" title="Manage Objectives for ' . $course['prefix'] . ' ' . $course['number'] . '" width="30" />';
									echo '</a>';
								}
								else
								{
									echo '-';
								}
							?>
						</td>
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