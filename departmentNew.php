<?php
	
	// Include Utilities PHP File
	require_once('_utilities.php');
	
	
	// Initialize Error Array
	$errors = array();
	
	
	// Start Session
	session_start();
	
	
	// Connect To Database
	$pdo = dbConnect();
	
	
	// Require Super User Login
	requireLogin(LEVEL_SU);
	
	
	// Handle Cancel Button
	if (isset($_POST['addDepartmentCancel']) && $_POST['addDepartmentCancel'])
    {
        redirect('departmentsManage.php');
    }
	
	
	// Handle Submit Button
	if (isset($_POST['addDepartmentSubmit']) && $_POST['addDepartmentSubmit'])
	{
		// Validate Department Name
		if (isblank($_POST['name']))
        {
            $errors[] = "Department name must not be empty.";
        }
		
		
		// Submit New Department to Database
		if (count($errors) == 0)
		{
			// 1. Send Query
			$stmt = $pdo->prepare("INSERT INTO `Department` SET `name` = :name, `dateCreated` = NOW()");
			$stmt->bindValue(':name', ucfirst(strtolower(parseStr($_POST['name']))));
			$success = $stmt->execute();
			
			// 2. Check for Errors
			if (!$success)
			{
				// Duplicate Department Name
				if (strcmp($stmt->errorCode(), '23000') == 0)
                {
                    $errors[] = "Department already exists in database.";
                }
				
				// Unknown Error
				else
                {
                    $errors[] = "Unknown database error occurred.";
                }
			}
			
			// 3. On Success, Refresh Page To Reflect New Department
			else
            {
                redirect('departmentsManage.php');
            }
		}
	}
	
	
	// Populate Form Data With Correct Values
	$PAGEDATA = array();
	$PAGEDATA['name'] = ucfirst(strtolower(parseStr($_POST['name'])));
	
?>




<!DOCTYPE html>
<html lang="en">
	<head>
		<!-- Title and Icon -->
		<title>eAssess CCU - Add Department</title>
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
				<h2 class="title">Add Department</h2>
				
				<?php outputErrorsHTML($errors); ?>
					
				<form name="addDepartment" action="" method="POST">
					<table class="formTable padded widthFull">
						<tr>
							<th>Department Name</th>
							<td><input name="name" value="<?php echo $PAGEDATA['name']; ?>" type="text" autofocus /></td>
						</tr>
						<tr>
							<td colspan="2"><input name="addDepartmentSubmit" type="submit" value="Add Department" /><input name="addDepartmentCancel" type="submit" value="Cancel" /></td>
						</tr>
					</table>
				</form>
			</section>
		</div>
		
		<?php require('html/footer.php'); ?>
	</body>
</html>