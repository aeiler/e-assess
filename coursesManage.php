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
	requireLogin(LEVEL_ADMIN, LEVEL_SU);
	
	
	// Retrieve Current List of Courses
	$query = "
		SELECT
			`Course`.`ID`,
			`Course`.`name`,
			`Course`.`prefix`,
			`Course`.`number`,
			`Course`.`status`
		FROM `Course`
		WHERE
			`Course`.`fkDepartment` = :fkDepartment
		ORDER BY `Course`.`status` DESC, `Course`.`prefix`, `Course`.`number`
	";
	$stmt = $pdo->prepare($query);
	$stmt->bindValue(':fkDepartment', $_SESSION['fkDepartment']);
	$success = $stmt->execute();
	if (!$success || ($courses = $stmt->fetchAll(PDO::FETCH_ASSOC)) === FALSE) redirect('index.php');
	
	
?>




<!DOCTYPE html>
<html lang="en">
	<head>
		<!-- Title and Icon -->
		<title>eAssess CCU - Manage Courses</title>
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
				<h2 class="title">Manage Courses</h2>
				
				<a href="courseNew.php" class="button">Create a New Course</a>
				
				<table class="verticalTable padded widthFull">
					<tr>
						<th>Code</th>
						<th>Name</th>
						<th>Edit</th>
						<th>Manage Objectives</th>
						<th>Manage Coordinators</th>
						<th>Disable/Enable</th>
					</tr>
					
					<?php
						foreach ($courses as $course)
						{
					?>
					<tr>
						<td><?php echo $course['prefix'] . ' ' . $course['number']; ?></td>
						<td><?php echo $course['name']; ?></td>
						<td class="center">
							<?php echo ($course['status']) ? '<a href="courseEdit.php?source=coursesManage.php&idCourse=' .  $course['ID'] . '"><img src="media/edit.png" title="Edit ' . $course['prefix'] . ' ' . $course['number'] . '" width="30" /></a>' : '-'; ?>
						</td>
						<td class="center">
							<?php echo ($course['status']) ? '<a href="objectivesManage.php?source=coursesManage.php&idCourse=' .  $course['ID'] . '"><img src="media/manage.png" title="Manage Objectives for ' . $course['prefix'] . ' ' . $course['number'] . '" width="30" /></a>' : '-'; ?>
						</td>
						<td class="center">
							<?php echo ($course['status']) ? '<a href="coordinatorEditCourse.php?idCourse=' . $course['ID'] . '"><img src="media/manage.png" title="Manage Coordinators for ' . $course['prefix'] . ' ' . $course['number'] . '" width="30" /></a>' : '-'; ?>
						</td>
						<td class="center">
							<a href="courseStatus.php?idCourse=<?php echo $course['ID']; ?>">
								<?php echo ($course['status']) ? '<img src="media/plus.png" title="Disable ' . $course['prefix'] . ' ' . $course['number'] . '" width="30" />' : '<img src="media/minus.png" title="Enable ' . $course['prefix'] . ' ' . $course['number'] . '" width="30" />'; ?>
							</a>
						</td>
					</tr>
					<?php
						}
						unset($course);
					?>
					
				</table>
				
				<a href="courseNew.php" class="button">Create a New Course</a>
			</section>
			
			<?php require('html/footer.php'); ?>
		</div>
	</body>
</html>