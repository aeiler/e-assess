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
	
	
	
	
	// Check Source and Set Redirect Path
	if (isset($_REQUEST['source']) && $_REQUEST['source'] == 'coursesManage.php')
	{
		$redirect = 'coursesManage.php';
	}
	else
	{
		$redirect = 'coordinatorManageCourses.php';
	}
	
	
	
	
	// Require Provided Course ID
	$restrictCoord = ($_SESSION['level'] == LEVEL_FACULTY) ? "AND `Coordinator`.`fkCourse` IS NOT NULL" : "";
	$query = "
		SELECT
			`Course`.`ID`,
			`Course`.`name`,
			`Course`.`description`,
			`Course`.`prefix`,
			`Course`.`number`,
			IF(`Coordinator`.`fkCourse` IS NULL, 0, 1) AS coordinator
		FROM `Course`
		LEFT JOIN `Coordinator`
		ON `Coordinator`.`fkCourse` = `Course`.`ID` AND `Coordinator`.`fkUser` = :fkUser
		WHERE
			`Course`.`ID` = :ID AND
			`Course`.`fkDepartment` = :fkDepartment AND
			`Course`.`status` = 1
			$restrictCoord
		LIMIT 1
	";
	$stmt = $pdo->prepare($query);
	$stmt->bindValue(':ID', parseInt($_REQUEST['idCourse']));
	$stmt->bindValue(':fkUser', $_SESSION['ID']);
	$stmt->bindValue(':fkDepartment', $_SESSION['fkDepartment']);
	$success = $stmt->execute();
	if (!$success || ($courseInfo = $stmt->fetch(PDO::FETCH_ASSOC)) === FALSE)
	{
		redirect($redirect);
	}
	
	
	
	
	// Handle Cancel Button
	if (isset($_POST['editCourseCancel']) && $_POST['editCourseCancel'])
	{
		redirect($redirect);
	}
	
	// Handle Submit Button
	if (isset($_POST['editCourseSubmit']) && $_POST['editCourseSubmit'])
	{
		// Validate Form Data
		$dbData = array();
		
		// 1. Check Course Name
		$dbData['name'] = ucwords(strtolower(parseStr($_POST['name'])));
		if (isblank($dbData['name']))
		{
			$errors[] = "Course name must not be empty.";
		}
		
		// 2. Retrieve Course Description
		$dbData['description'] = ucfirst(parseStr($_POST['description']));
		
		// 3. Check Course Prefix
		$dbData['prefix'] = strtoupper(parseStr($_POST['prefix']));
		if (strlen($dbData['prefix']) < 3 || strlen($dbData['prefix']) > 4)
		{
			$errors[] = "Invalid course prefix. Prefix must be between three and four characters long.";
		}
		
		// 4. Check Course Number
		$dbData['number'] = parseStr($_POST['number']);
		if (strlen($dbData['number']) < 3 || strlen($dbData['number']) > 4)
		{
			$errors[] = "Invalid course number. Number must be between three and four characters long.";
		}
		
		
		// Submit Course Changes to Database
		if (count($errors) == 0)
		{
			// 1. Prepare Data for Query
			$assigns = array();
			$params = array();
			
			// 1.1. Course ID
			$params[':ID'] = $courseInfo['ID'];
			
			// 1.2. Date Modified
			$assigns[] = "`dateModified` = NOW()";
			
			// 1.3. User Last Modified
			$assigns[] = "`fkUserModified` = :fkUserModified";
			$params[':fkUserModified'] = $_SESSION['ID'];
			
			// 1.4. Variables in $dbData
			foreach ($dbData as $key => $value)
			{
				$assigns[] = "`$key` = :$key";
				$params[':' . $key] = $value;
			}
			
			// 1.5. Implode Assignment Variable
			$assigns = implode(', ', $assigns);
			
			
			// 2. Send Query
			$stmt = $pdo->prepare("UPDATE `Course` SET $assigns WHERE `ID` = :ID");
			$success = $stmt->execute($params);
			
			
			// 3. Check for Errors
			if (!$success)
			{
				// Duplicate Course
				if (strcmp($stmt->errorCode(), '23000') == 0)
				{
					$errors[] = "Course code already exists in database.";
				}
				
				// Unknown Error
				else
				{
					$errors[] = "Unknown database error occurred.";
				}
			}
			
			
			// 4. On Success, Refresh Page To Reflect Course Changes
			else
			{
				redirect($redirect);
			}
		}
	}
	
	
	
	
	// Populate Form Data With Correct Values
	// 1. Create and Populate $PAGEDATA Associative Array To Hold Old Form Data
	$PAGEDATA = array();
	$PAGEDATA['name'] = ucwords(strtolower(parseStr($_POST['name'])));
	$PAGEDATA['description'] = ucfirst(parseStr($_POST['description']));
	$PAGEDATA['prefix'] = strtoupper(parseStr($_POST['prefix']));
	$PAGEDATA['number'] = parseStr($_POST['number']);
	
	// 2. If No Old Form Data Exists, Use Database For Current Data
	if (!isset($_POST['editCourseSubmit']) || !$_POST['editCourseSubmit'])
	{
		$PAGEDATA['name'] = ucwords(strtolower($courseInfo['name']));
		$PAGEDATA['description'] = ucfirst($courseInfo['description']);
		$PAGEDATA['prefix'] = strtoupper($courseInfo['prefix']);
		$PAGEDATA['number'] = $courseInfo['number'];
	}
?>




<!DOCTYPE html>
<html lang="en">
	<head>
		<!-- Title and Icon -->
		<title>eAssess CCU - Edit Course</title>
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
				<h2 class="title">Edit Course</h2>
				
				<?php outputErrorsHTML($errors); ?>
					
				<form name="editCourse" action="" method="POST">
					<table class="formTable padded widthFull">
						<tr>
							<th>Prefix</th>
							<td><input name="prefix" type="text" maxlength="4" value="<?php echo $PAGEDATA['prefix']; ?>" /></td>
						</tr>
						<tr>
							<th>Number</th>
							<td><input name="number" type="text" maxlength="4" value="<?php echo $PAGEDATA['number']; ?>" /></td>
						</tr>
						<tr>
							<th>Full Name</th>
							<td><input name="name" type="text" value="<?php echo $PAGEDATA['name']; ?>" /></td>
						</tr>
						<tr>
							<th>Description</th>
							<td><textarea name="description"><?php echo $PAGEDATA['description']; ?></textarea></td>
						</tr>
						<tr>
							<td colspan="2"><input name="editCourseSubmit" type="submit" value="Save Changes" /><input name="editCourseCancel" type="submit" value="Cancel" /></td>
						</tr>
					</table>
					
					<input name="source" type="hidden" value="<?php echo parseStr($_REQUEST['source']); ?>" />
					<input name="idCourse" type="hidden" value="<?php echo $courseInfo['ID']; ?>" />
				</form>
			</section>
			
			<?php require('html/footer.php'); ?>
		</div>
	</body>
</html>