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
	if (!isset($_SESSION['level'])) redirect('login.php');
	
?>




<!DOCTYPE html>
<html lang="en">
	<head>
		<!-- Title and Icon -->
		<title>eAssess CCU - Home</title>
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
			<section id="facultyBox" class="indexBox shadow corner center">
				<h2>Faculty Menu</h2>
				
				<ul>
					<li><a href="degreesView.php">View Degrees</a></li>
					<li><a href="SLOsView.php">View SLOs</a></li>
					<li><a href="coursesView.php">View Courses</a></li>
					<li><a href="syllabusNew.php">Create Class Syllabus<a></li>
					<li><a href="coordinatorManageCourses.php">Manage Coordinator Courses and Objectives</a></li>
					<li><a href="FCARsManage.php">Manage My Assessment Reports</a></li>
				</ul>
			</section>
			
			<?php
				if ($_SESSION['level'] == LEVEL_ADMIN || $_SESSION['level'] == LEVEL_SU)
				{
			?>
			
			<section id="adminBox" class="indexBox shadow corner center">
				<h2>Department Administrator Menu</h2>
				
				<ul>
					<li><a href="usersManage.php">Manage Users</a></li>
					<li><a href="degreesManage.php">Manage Degrees</a></li>
					<li><a href="SLOsManage.php">Manage SLOs</a></li>
					<li><a href="coursesManage.php">Manage Courses and Objectives</a></li>
					<li><a href="FCARsList.php">View Submitted Assessment Reports</a></li>
				</ul>
			</section>
			
			<?php
				}
				if ($_SESSION['level'] == LEVEL_SU)
				{
			?>
			
			<section id="SUBox" class="indexBox shadow corner center">
				<h2>System Administrator Menu</h2>
				
				<ul>
					<li><a href="departmentsManage.php">Manage Departments</a></li>
				</ul>
			</section>
			
			<?php
				}
			?>
			
			<?php require('html/footer.php'); ?>
		</div>
	</body>
</html>