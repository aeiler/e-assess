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
	
	
	
	
	// Retrieve Current List of Courses
	$query = "
		SELECT DISTINCT
			`Course`.`ID`,
			`Course`.`name`,
			`Course`.`description`,
			`Course`.`prefix`,
			`Course`.`number`
		FROM `Course`
		JOIN `Objective`
		ON `Objective`.`fkCourse` = `Course`.`ID`
		JOIN `SLOXObjective`
		ON `SLOXObjective`.`fkObjective` = `Objective`.`ID`
		JOIN `SLO`
		ON `SLO`.`ID` = `SLOXObjective`.`fkSLO`
		WHERE
			`Course`.`status` = 1 AND
			`Objective`.`status` = 1 AND
			`SLOXObjective`.`dateDisabled` IS NULL AND
			`SLO`.`status` = 1 AND
			`Course`.`fkDepartment` = :fkDepartment
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
		<title>eAssess CCU - Create Syllabus</title>
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
				<h2 class="title">Create Syllabus</h2>
				
				<?php outputErrorsHTML($errors); ?>
				
				<form name="addSyllabus" action="syllabusExport.php" method="POST">
					<table class="formTable padded widthFull">
						<tr>
							<th>Academic Year</th>
							<td>
								<select name="year">
									<?php
										for ($i=-1; $i<2; $i++)
										{
											$year = intval(strftime('%Y')) + $i;
											echo '<option value="' . $year . '"';
											if (strcmp(strftime('%Y'), $year) == 0)
											{
												echo ' selected';
											}
											echo '>' . $year . '</option>';
										}
									?>
								</select>
							</td>
						</tr>
						<tr>
							<th>Academic Term</th>
							<td>
								<select name="term">
									<?php
										$terms = array(
											'FA' => 'Fall',
											'WI' => 'Winter',
											'SP' => 'Spring',
											'MY' => 'May',
											'S1' => 'Summer 1',
											'S2' => 'Summer 2'
										);
										
										foreach ($terms as $key => $value)
										{
											echo '<option value="' . $key . '">' . $key . ' ' . $value . '</option>' . "\n";
										}
									?>
								</select>
							</td>
						</tr>
						<tr>
							<th>Course</th>
							<td>
								<select name="course">
									<option value="" selected></option>
									<?php
										foreach($courses as $course)
										{
											echo '<option value="' . $course['ID'] . '">' . $course['prefix'] . ' ' . $course['number'] . ' (' . $course['name'] . ')</option>' . "\n";
										}
										unset($course);
									?>
								</select>
							</td>
						</tr>
						<tr>
							<th>Section</th>
							<td><input name="section" type="text" /></td>
						</tr>
						<tr>
							<td colspan="2"><input name="addSyllabusSubmit" type="submit" value="Export Word Document" /> <a href="index.php" class="button">Cancel</a></td>
						</tr>
					</table>
				</form>
			</section>
			
			<?php require('html/footer.php'); ?>
		</div>
	</body>
</html>