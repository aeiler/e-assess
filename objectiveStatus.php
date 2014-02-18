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
	
	
	
	
	// Require Provided Objective ID
	// 1. Attempt to Query Database for Supplied Objective's Info
	$query = "
		SELECT DISTINCT
			`Objective`.`ID` AS ID,
			`Objective`.`fkCourse` AS fkCourse,
			`Objective`.`description` AS description,
			`Objective`.`number` AS number,
			`Objective`.`status` AS status,
			IF(`Coordinator`.`fkUser` IS NULL, 0, 1) AS coordinator
		FROM `Objective`
		JOIN `Course`
		ON `Course`.`ID` = `Objective`.`fkCourse`
		LEFT JOIN `Coordinator`
		ON `Coordinator`.`fkCourse` = `Course`.`ID` AND `Coordinator`.`fkUser` = :fkUser
		WHERE
			`Objective`.`ID` = :ID AND
			`Course`.`fkDepartment` = :fkDepartment AND
			`Course`.`status` = 1
		LIMIT 1
	";
	$stmt = $pdo->prepare($query);
	$stmt->bindValue(':ID', parseInt($_REQUEST['idObjective']));
	$stmt->bindValue(':fkDepartment', $_SESSION['fkDepartment']);
	$stmt->bindValue(':fkUser', $_SESSION['ID']);
	$success = $stmt->execute();
	
	// 2. Ensure a Valid Objective Was Found in the Database
	if (!$success || ($objectiveInfo = $stmt->fetch(PDO::FETCH_ASSOC)) === FALSE) redirect('coursesManage.php');
	
	// 3. If Logged In User Is Faculty, Check For Coordinator Permissions
	if ($_SESSION['level'] == LEVEL_FACULTY && $courseInfo['coordinator'] === 0) redirect('coursesManage.php');
	
	
	
	
	// Handle Cancel Button
	if (isset($_POST['statusObjectiveCancel']) && $_POST['statusObjectiveCancel']) redirect('objectivesManage.php', array('idCourse' => $objectiveInfo['fkCourse']));
	
	// Handle Submit Button
	if (isset($_POST['statusObjectiveSubmit']) && $_POST['statusObjectiveSubmit'])
	{
		// 1. Send Query
		$stmt = $pdo->prepare(
			"UPDATE `Objective`
			SET
				`status` = ABS(`status` - 1),
				`fkUserModified` = :fkUserModified,
				`dateModified` = NOW()
			WHERE `ID` = :ID
		");
		$stmt->bindValue(':ID', $objectiveInfo['ID']);
		$stmt->bindValue(':fkUserModified', $_SESSION['ID']);
		$success = $stmt->execute();
		
		// 2. Check for Errors
		if (!$success) $errors[] = "Unknown database error occurred.";
		
		// 3. On Success, Redirect Page
		else redirect('objectivesManage.php', array('idCourse' => $objectiveInfo['fkCourse']));
	}
	
?>




<!DOCTYPE html>
<html lang="en">
	<head>
		<!-- Title and Icon -->
		<title>eAssess CCU - <?php echo ($objectiveInfo['status']) ? 'Disable' : 'Enable'; ?> Objective</title>
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
				<h2 class="title"><?php echo ($objectiveInfo['status']) ? 'Disable' : 'Enable'; ?> Objective</h2>
				
				<?php outputErrorsHTML($errors); ?>
				
				<p>Are you sure you want to <?php echo ($objectiveInfo['status']) ? 'disable' : 'enable'; ?> objective number <?php echo $objectiveInfo['number']; ?>?</p>
				
				<form name="statusObjective" action="" method="POST">
					<table class="formTable padded widthFull">
						<tr>
							<td colspan="2"><input name="statusObjectiveSubmit" type="submit" value="<?php echo ($objectiveInfo['status']) ? 'Disable' : 'Enable'; ?>" /><input name="statusObjectiveCancel" type="submit" value="Cancel" /></td>
						</tr>
					</table>
				</form>
			</section>
			
			<?php require('html/footer.php'); ?>
		</div>
	</body>
</html>