<?php
	
	// Include Utilities PHP File
	require_once('_utilities.php');
	
	
	// Initialize Error Array
	$errors = array();
	
	
	// Start Session
	session_start();
	
	
	// Connect To Database
	$pdo = dbConnect();
	
	
	// Require Admin or Super User Login
	requireLogin(LEVEL_ADMIN, LEVEL_SU);
	
	
	
	
	// Require Provided FCAR ID
	$query = "
		SELECT
			`FCAR`.`ID` AS ID,
			`FCAR`.`year`,
			`FCAR`.`term`,
			`FCAR`.`section`,
			`User`.`firstName`,
			`User`.`lastName`,
			`Degree`.`name` AS degreeName,
			`Degree`.`code`,
			`Course`.`name` AS courseName,
			`Course`.`prefix`,
			`Course`.`number`
		FROM `FCAR`
		JOIN `Degree`
		ON `Degree`.`ID` = `FCAR`.`fkDegree`
		JOIN `Course`
		ON `Course`.`ID` = `FCAR`.`fkCourse`
		JOIN `User`
		ON `User`.`ID` = `FCAR`.`fkUser`
		WHERE
			`User`.`fkDepartment` = :fkDepartment AND
			`FCAR`.`status` = 1 AND
			`FCAR`.`ID` = :ID AND
			`FCAR`.`dateSubmitted` IS NOT NULL
		LIMIT 1
	";
	$stmt = $pdo->prepare($query);
	$stmt->bindValue(':fkDepartment', $_SESSION['fkDepartment']);
	$stmt->bindValue(':ID', parseInt($_REQUEST['idFCAR']));
	$success = $stmt->execute();
	if (!$success || ($FCARInfo = $stmt->fetch(PDO::FETCH_ASSOC)) === FALSE) redirect('FCARsList.php');
	
	
	
	
	// Handle Cancel Button
	if (isset($_POST['reopenFCARCancel']) && $_POST['reopenFCARCancel']) redirect('FCARsList.php');
	
	
	// Handle Submit Button
	if (isset($_POST['reopenFCARSubmit']) && $_POST['reopenFCARSubmit'])
	{
		// 1. Send Query
		$stmt = $pdo->prepare(
			"UPDATE `FCAR`
			SET
				`dateModified` = NOW(),
				`dateSubmitted` = NULL
			WHERE `ID` = :ID
		");
		$stmt->bindValue(':ID', $FCARInfo['ID']);
		$success = $stmt->execute();
		
		// 2. Check for Errors
		if (!$success) $errors[] = "Unknown database error occurred.";
		
		// 3. On Success, Redirect Page
		else redirect('FCARsList.php');
	}
	
?>




<!DOCTYPE html>
<html lang="en">
	<head>
		<!-- Title and Icon -->
		<title>eAssess CCU - Reopen Assessment Report</title>
		<link rel="icon" href="media/favicon.ico" />
		
		<!-- Meta Information -->
		<meta charset="utf-8" />
		
		<!-- Stylesheets -->
		<link rel="stylesheet" type="text/css" href="css/_reset.css" />
		<link rel="stylesheet" type="text/css" href="css/_globalStyles.css" />
		<link rel="stylesheet" type="text/css" href="css/_FCARStyles.css" />
	</head>

	<body>
		<?php require('html/header.php'); ?>
		
		<div id="siteContainer" class="pageWidth">
			<section class="bgField shadow corner center">
				<h2 class="title">Reopen Assessment Report</h2>
				
				<?php outputErrorsHTML($errors); ?>
				
				<p>Are you sure you want to reopen the following Assessment Report for edits?</p>
				
				<table>
					<tr>
						<th>User</th>
						<th>Class</th>
						<th>Course Name</th>
						<th>Academic Term</th>
						<th>Degree</th>
					</tr>
					
					<tr>
						<td><?php echo $FCARInfo['lastName'] . ', ' . $FCARInfo['firstName']; ?></td>
						<td><?php echo $FCARInfo['prefix'] . ' ' . $FCARInfo['number'] . ' ' . $FCARInfo['section']; ?></td>
						<td><?php echo $FCARInfo['courseName']; ?></td>
						<td><?php echo $FCARInfo['term'] . ' ' . $FCARInfo['year']; ?></td>
						<td><?php echo $FCARInfo['degreeName'] . ' (' . $FCARInfo['code'] . ')'; ?></td>
					</tr>
				</table>
				
				<form name="reopenFCAR" action="" method="POST">
					<p>
						<input name="reopenFCARSubmit" type="submit" value="Reopen" />
						<input name="reopenFCARCancel" type="submit" value="Cancel" />
					</p>
				</form>
			</section>
			
			<?php require('html/footer.php'); ?>
		</div>
	</body>
</html>