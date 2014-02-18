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
	
	
	
	
	// Require Provided FCAR ID
	$query = "
		SELECT
			`FCAR`.`ID` AS ID,
			`FCAR`.`year`,
			`FCAR`.`term`,
			`FCAR`.`section`,
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
		WHERE
			`FCAR`.`fkUser` = :fkUser AND
			`FCAR`.`status` = 1 AND
			`FCAR`.`ID` = :ID AND
			`FCAR`.`dateSubmitted` IS NULL
		LIMIT 1
	";
	$stmt = $pdo->prepare($query);
	$stmt->bindValue(':fkUser', $_SESSION['ID']);
	$stmt->bindValue(':ID', parseInt($_REQUEST['idFCAR']));
	$success = $stmt->execute();
	if (!$success || ($FCARInfo = $stmt->fetch(PDO::FETCH_ASSOC)) === FALSE) redirect('FCARsManage.php');
	
	
	
	
	// Handle Cancel Button
	if (isset($_POST['disableFCARCancel']) && $_POST['disableFCARCancel']) redirect('FCARsManage.php');
	
	
	// Handle Submit Button
	if (isset($_POST['disableFCARSubmit']) && $_POST['disableFCARSubmit'])
	{
		// 1. Send Query
		$stmt = $pdo->prepare(
			"UPDATE `FCAR`
			SET
				`status` = 0,
				`dateModified` = NOW()
			WHERE `ID` = :ID
		");
		$stmt->bindValue(':ID', $FCARInfo['ID']);
		$success = $stmt->execute();
		
		// 2. Check for Errors
		if (!$success) $errors[] = "Unknown database error occurred.";
		
		// 3. On Success, Redirect Page
		else redirect('FCARsManage.php');
	}
	
?>




<!DOCTYPE html>
<html lang="en">
	<head>
		<!-- Title and Icon -->
		<title>eAssess CCU - Delete Assessment Report</title>
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
				<h2 class="title">Delete Assessment Report</h2>
				
				<?php outputErrorsHTML($errors); ?>
				
				<p>Are you sure you want to permanently delete the following Assessment Report? <strong>This action is irreversible!</strong></p>
				
				<table>
					<tr>
						<th>Class</th>
						<th>Course Name</th>
						<th>Academic Term</th>
						<th>Degree</th>
					</tr>
					
					<tr>
						<td><?php echo $FCARInfo['prefix'] . ' ' . $FCARInfo['number'] . ' ' . $FCARInfo['section']; ?></td>
						<td><?php echo $FCARInfo['courseName']; ?></td>
						<td><?php echo $FCARInfo['term'] . ' ' . $FCARInfo['year']; ?></td>
						<td><?php echo $FCARInfo['degreeName'] . ' (' . $FCARInfo['code'] . ')'; ?></td>
					</tr>
				</table>
				
				<form name="disableFCAR" action="" method="POST">
					<p>
						<input name="disableFCARSubmit" type="submit" value="Delete" />
						<input name="disableFCARCancel" type="submit" value="Cancel" />
					</p>
				</form>
			</section>
			
			<?php require('html/footer.php'); ?>
		</div>
	</body>
</html>