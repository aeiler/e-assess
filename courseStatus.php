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
	$restrictCoord = ($_SESSION['level'] == LEVEL_FACULTY) ? "AND `Coordinator`.`fkCourse` IS NOT NULL" : "";
	$query = "
		SELECT
			`Course`.`ID`,
			`Course`.`name`,
			`Course`.`description`,
			`Course`.`prefix`,
			`Course`.`number`,
			`Course`.`status`,
			IF(`Coordinator`.`fkCourse` IS NULL, 0, 1) AS coordinator
		FROM `Course`
		LEFT JOIN `Coordinator`
		ON `Coordinator`.`fkCourse` = `Course`.`ID` AND `Coordinator`.`fkUser` = :fkUser
		WHERE
			`Course`.`ID` = :ID AND
			`Course`.`fkDepartment` = :fkDepartment
			$restrictCoord
		LIMIT 1
	";
	$stmt = $pdo->prepare($query);
	$stmt->bindValue(':ID', parseInt($_REQUEST['idCourse']));
	$stmt->bindValue(':fkUser', $_SESSION['ID']);
	$stmt->bindValue(':fkDepartment', $_SESSION['fkDepartment']);
	$success = $stmt->execute();
	if (!$success || ($courseInfo = $stmt->fetch(PDO::FETCH_ASSOC)) === FALSE) redirect('coursesManage.php');
	
	
	
	
	// Handle Cancel Button
	if (isset($_POST['statusCourseCancel']) && $_POST['statusCourseCancel']) redirect('coursesManage.php');
	
	
	// Handle Submit Button
	if (isset($_POST['statusCourseSubmit']) && $_POST['statusCourseSubmit'])
	{
		// 1. Send Query
		$stmt = $pdo->prepare(
			"UPDATE `Course`
			SET
				`status` = ABS(`status` - 1),
				`fkUserModified` = :fkUserModified,
				`dateModified` = NOW()
			WHERE `ID` = :ID
		");
		$stmt->bindValue(':ID', $courseInfo['ID']);
		$stmt->bindValue(':fkUserModified', $_SESSION['ID']);
		$success = $stmt->execute();
		
		// 2. Check for Errors
		if (!$success) $errors[] = "Unknown database error occurred.";
		
		// 3. On Success, Redirect Page
		else redirect('coursesManage.php');
	}
	
?>




<!DOCTYPE html>
<html lang="en">
	<head>
		<!-- Title and Icon -->
		<title>eAssess CCU - <?php echo ($courseInfo['status']) ? 'Disable' : 'Enable'; ?> Course</title>
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
				<h2 class="title"><?php echo ($courseInfo['status']) ? 'Disable' : 'Enable'; ?> Course</h2>
				
				<?php outputErrorsHTML($errors); ?>
				
				<p>Are you sure you want to <?php echo ($courseInfo['status']) ? 'disable' : 'enable'; ?> course "<?php echo $courseInfo['name']; ?>"?</p>
				
				<form name="statusCourse" action="" method="POST">
					<table class="formTable padded widthFull">
						<tr>
							<td colspan="2"><input name="statusCourseSubmit" type="submit" value="<?php echo ($courseInfo['status']) ? 'Disable' : 'Enable'; ?>" /><input name="statusCourseCancel" type="submit" value="Cancel" /></td>
						</tr>
					</table>
				</form>
			</section>
			
			<?php require('html/footer.php'); ?>
		</div>
	</body>
</html>