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
	
	
	
	
	// Require Provided User ID
	// 1. Attempt to Query Database for Supplied User's Info
	$query = "
		SELECT
			`ID`,
			`firstName`,
			`lastName`,
			`username`,
			`fkDepartment`
		FROM `User`
		WHERE `ID` = :ID AND `level` <= :level AND `User`.`status` = 1
		LIMIT 1
	";
	$stmt = $pdo->prepare($query);
	$stmt->bindValue(':ID', parseInt($_REQUEST['idUser']));
	$stmt->bindValue(':level', $_SESSION['level']);
	$success = $stmt->execute();
	
	// 2. Ensure a Valid Faculty-Level User Was Found in the Database
	if (!$success || ($userInfo = $stmt->fetch(PDO::FETCH_ASSOC)) === FALSE)
	{
		redirect('usersManage.php');
	}
	
	// 3. If Logged In User Is Admin, Check Department
	if ($_SESSION['level'] != LEVEL_SU && $_SESSION['fkDepartment'] != $userInfo['fkDepartment'])
	{
		redirect('usersManage.php');
	}
	
	
	
	
	// Retrieve Course Info From Database
	$query = "
		SELECT
			`Course`.`ID`,
			`Course`.`name`,
			`Course`.`prefix`,
			`Course`.`number`,
			IF(`Coordinator`.`fkUser` IS NULL, 0, 1) AS coordinator
		FROM `Course`
		LEFT JOIN `Coordinator`
		ON `Coordinator`.`fkCourse` = `Course`.`ID` AND `Coordinator`.`fkUser` = :fkUser
		WHERE `Course`.`fkDepartment` = :fkDepartment AND `Course`.`status` = 1
		ORDER BY `Course`.`prefix`, `Course`.`number`
	";
	$stmt = $pdo->prepare($query);
	$stmt->bindValue(':fkDepartment', $userInfo['fkDepartment']);
	$stmt->bindValue(':fkUser', $userInfo['ID']);
	$success = $stmt->execute();
	if (!$success || ($courses = $stmt->fetchAll()) === FALSE)
	{
		redirect('usersManage.php');
	}
	
	
	
	
	// Handle Cancel Button
	if (isset($_POST['editCoordinatorCancel']) && $_POST['editCoordinatorCancel']) redirect('usersManage.php');
	
	// Handle Submit Button
	if (isset($_POST['editCoordinatorSubmit']) && $_POST['editCoordinatorSubmit'])
	{
		// Submit Coordinator Changes to Database
		if (count($errors) == 0)
		{
			// 1. Loop Through Courses
			foreach ($courses as $course)
			{
				$oldMap = $course['coordinator'];
				$newMap = (isset($_POST['coordinator']) && in_array($course['ID'], $_POST['coordinator']));
				
				if ($oldMap && !$newMap)
				{
					$stmt = $pdo->prepare("DELETE FROM `Coordinator` WHERE fkCourse = :fkCourse AND fkUser = :fkUser");
					$stmt->bindValue(':fkCourse', $course['ID']);
					$stmt->bindValue(':fkUser', $userInfo['ID']);
					$success = $stmt->execute();
					if (!$success) $errors[] = "Internal database error: Could not remove old coordinator mapping.";
				}
				else if (!$oldMap && $newMap)
				{
					$stmt = $pdo->prepare("INSERT INTO `Coordinator` SET fkCourse = :fkCourse, fkUser = :fkUser");
					$stmt->bindValue(':fkCourse', $course['ID']);
					$stmt->bindValue(':fkUser', $userInfo['ID']);
					$success = $stmt->execute();
					if (!$success) $errors[] = "Internal database error: Could not add new coordinator mapping.";
				}
			}
			unset($course);
			
			
			// 2. On Success, Refresh Page To Reflect New Coordinator Changes
			if (count($errors) == 0) redirect('usersManage.php');
		}
	}
	
	
	
	
	// Populate Form Data With Correct Values - Use Old Form Data to Populate Fields, if Available
	if (isset($_POST['editCoordinatorSubmit']) && $_POST['editCoordinatorSubmit'])
	{
		foreach ($courses as &$course)
			$course['coordinator'] = (isset($_POST['coordinator']) && in_array($course['ID'], $_POST['coordinator'])) ? 1 : 0;
		unset($course);
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
				<h2 class="title">Assign Coordinator Classes for <?php echo $userInfo['username']; ?></h2>
				
				<?php outputErrorsHTML($errors); ?>
					
				<form name="editCoordinator" action="" method="POST">
					<table class="formTable padded widthFull">
						<tr>
							<th>Coordinator Classes</th>
							<td>
								<?php
									foreach($courses as $course)
									{
										echo '<label>';
										echo '<input type="checkbox" name="coordinator[]" value="' . $course['ID'] . '"';
										if ($course['coordinator']) echo ' checked ';
										echo '>' . $course['prefix'] . ' ' . $course['number'] . ' ' . $course['name'];
										echo '</label><br />';
									}
									unset($course);
								?>
							</td>
						</tr>
						<tr>
							<td colspan="2"><input name="editCoordinatorSubmit" type="submit" value="Save Changes" /><input name="editCoordinatorCancel" type="submit" value="Cancel" /></td>
						</tr>
					</table>
					
					<input name="idUser" type="hidden" value="<?php echo $userInfo['ID']; ?>" />
				</form>
			</section>
			
			<?php require('html/footer.php'); ?>
		</div>
	</body>
</html>