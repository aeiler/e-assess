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
	
	
	
	
	// Handle Cancel Button
	if (isset($_POST['addCourseCancel']) && $_POST['addCourseCancel'])
	{
		redirect('coursesManage.php');
	}
	
	
	// Handle Submit Button
	if (isset($_POST['addCourseSubmit']) && $_POST['addCourseSubmit'])
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
		
		
		
		
		// Submit New Course to Database
		if (count($errors) == 0)
		{
			// 1. Prepare Data for Query
			$assigns = array();
			$params = array();
			
			// 1.1. Date Created
			$assigns[] = "`dateCreated` = NOW()";
			
			// 1.2. Date Modified
			$assigns[] = "`dateModified` = NOW()";
			
			// 1.3. User Last Modified
			$assigns[] = "`fkUserModified` = :fkUserModified";
			$params[':fkUserModified'] = $_SESSION['ID'];
			
			// 1.4. Department
			$assigns[] = "`fkDepartment` = :fkDepartment";
			$params[':fkDepartment'] = $_SESSION['fkDepartment'];
			
			// 1.5. Variables in $dbData
			foreach ($dbData as $key => $value)
			{
				$assigns[] = "`$key` = :$key";
				$params[':' . $key] = $value;
			}
			
			// 1.6. Implode Assignment Variable
			$assigns = implode(', ', $assigns);
			
			
			// 2. Send Query
			$stmt = $pdo->prepare("INSERT INTO `Course` SET $assigns");
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
			
			
			// 4. On Success, Refresh Page To Reflect New Course
			else
			{
				redirect('coursesManage.php');
			}
		}
	}
	
	
	
	
	// Populate Form Data With Correct Values
	$PAGEDATA = array();
	$PAGEDATA['name'] = ucwords(strtolower(parseStr($_POST['name'])));
	$PAGEDATA['description'] = ucfirst(parseStr($_POST['description']));
	$PAGEDATA['prefix'] = strtoupper(parseStr($_POST['prefix']));
	$PAGEDATA['number'] = parseStr($_POST['number']);
	
?>




<!DOCTYPE html>
<html lang="en">
	<head>
		<!-- Title and Icon -->
		<title>eAssess CCU - Add Course</title>
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
				<h2 class="title">Add Course</h2>
				
				<?php outputErrorsHTML($errors); ?>
					
				<form name="addCourse" action="" method="POST">
					<table class="formTable padded widthFull">
						<tr>
							<th>Prefix</th>
							<td><input name="prefix" type="text" maxlength="4" value="<?php echo $PAGEDATA['prefix']; ?>" autofocus /></td>
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
							<td colspan="2"><input name="addCourseSubmit" type="submit" value="Add Course" /><input name="addCourseCancel" type="submit" value="Cancel" /></td>
						</tr>
					</table>
				</form>
			</section>
			
			<?php require('html/footer.php'); ?>
		</div>
	</body>
</html>