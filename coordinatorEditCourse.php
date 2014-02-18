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
	
	
	
	
	// Require Provided Course ID
	$query = "
		SELECT
			`ID`,
			`name`,
			`description`,
			`prefix`,
			`number`
		FROM `Course`
		WHERE `ID` = :ID AND `Course`.`fkDepartment` = :fkDepartment AND `Course`.`status` = 1
		LIMIT 1
	";
	$stmt = $pdo->prepare($query);
	$stmt->bindValue(':ID', parseInt($_REQUEST['idCourse']));
	$stmt->bindValue(':fkDepartment', $_SESSION['fkDepartment']);
	$success = $stmt->execute();
	if (!$success || ($courseInfo = $stmt->fetch(PDO::FETCH_ASSOC)) === FALSE) redirect('coursesManage.php');
	
	
	
	
	// Retrieve User Info From Database
	$query = "
		SELECT
			`User`.`ID`,
			`User`.`firstName`,
			`User`.`lastName`,
			IF(`Coordinator`.`fkUser` IS NULL, 0, 1) AS coordinator
		FROM `User`
		LEFT JOIN `Coordinator`
		ON `Coordinator`.`fkUser` = `User`.`ID` AND `Coordinator`.`fkCourse` = :fkCourse
		WHERE `User`.`fkDepartment` = :fkDepartment AND `User`.`status` = 1
		ORDER BY `User`.`lastName`, `User`.`firstName`
	";
	$stmt = $pdo->prepare($query);
	$stmt->bindValue(':fkDepartment', $_SESSION['fkDepartment']);
	$stmt->bindValue(':fkCourse', $courseInfo['ID']);
	$success = $stmt->execute();
	if (!$success || ($users = $stmt->fetchAll()) === FALSE) redirect('coursesManage.php');
	
	
	
	
	// Handle Cancel Button
	if (isset($_POST['editCoordinatorCancel']) && $_POST['editCoordinatorCancel']) redirect('coursesManage.php');
	
	// Handle Submit Button
	if (isset($_POST['editCoordinatorSubmit']) && $_POST['editCoordinatorSubmit'])
	{
		// Submit Coordinator Changes to Database
		if (count($errors) == 0)
		{
			// 1. Loop Through Users
			foreach ($users as $user)
			{
				$oldMap = $user['coordinator'];
				$newMap = (isset($_POST['coordinator']) && in_array($user['ID'], $_POST['coordinator']));
				
				if ($oldMap && !$newMap)
				{
					$stmt = $pdo->prepare("DELETE FROM `Coordinator` WHERE fkCourse = :fkCourse AND fkUser = :fkUser");
					$stmt->bindValue(':fkCourse', $courseInfo['ID']);
					$stmt->bindValue(':fkUser', $user['ID']);
					$success = $stmt->execute();
					if (!$success) $errors[] = "Internal database error: Could not remove old coordinator mapping.";
				}
				else if (!$oldMap && $newMap)
				{
					$stmt = $pdo->prepare("INSERT INTO `Coordinator` SET fkCourse = :fkCourse, fkUser = :fkUser");
					$stmt->bindValue(':fkCourse', $courseInfo['ID']);
					$stmt->bindValue(':fkUser', $user['ID']);
					$success = $stmt->execute();
					if (!$success) $errors[] = "Internal database error: Could not add new coordinator mapping.";
				}
			}
			unset($user);
			
			
			// 2. On Success, Refresh Page To Reflect New Coordinator Changes
			if (count($errors) == 0) redirect('coursesManage.php');
		}
	}
	
	
	
	
	// Populate Form Data With Correct Values - Use Old Form Data to Populate Fields, if Available
	if (isset($_POST['editCoordinatorSubmit']) && $_POST['editCoordinatorSubmit'])
	{
		foreach ($users as &$user)
			$user['coordinator'] = (isset($_POST['coordinator']) && in_array($user['ID'], $_POST['coordinator'])) ? 1 : 0;
		unset($user);
	}
?>




<!DOCTYPE html>
<html lang="en">
	<head>
		<!-- Title and Icon -->
		<title>eAssess CCU - Assign Coordinator Classes</title>
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
				<h2 class="title">Assign Coordinator Classes for <?php echo $courseInfo['prefix'] . ' ' . $courseInfo['number']; ?></h2>
				
				<?php outputErrorsHTML($errors); ?>
					
				<form name="editCoordinator" action="" method="POST">
					<table class="formTable padded widthFull">
						<tr>
							<th>Coordinator Classes</th>
							<td>
								<?php
									foreach($users as $user)
									{
										echo '<label>';
										echo '<input type="checkbox" name="coordinator[]" value="' . $user['ID'] . '"';
										if ($user['coordinator']) echo ' checked ';
										echo '>' . $user['lastName'] . ', ' . $user['firstName'];
										echo '</label><br />';
									}
									unset($user);
								?>
							</td>
						</tr>
						<tr>
							<td colspan="2"><input name="editCoordinatorSubmit" type="submit" value="Save Changes" /><input name="editCoordinatorCancel" type="submit" value="Cancel" /></td>
						</tr>
					</table>
					
					<input name="idCourse" type="hidden" value="<?php echo $courseInfo['ID']; ?>" />
				</form>
			</section>
			
			<?php require('html/footer.php'); ?>
		</div>
	</body>
</html>