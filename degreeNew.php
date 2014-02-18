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
	if (isset($_POST['addDegreeCancel']) && $_POST['addDegreeCancel'])
    {
        redirect('degreesManage.php');
    }
	
	
	// Handle Submit Button
	if (isset($_POST['addDegreeSubmit']) && $_POST['addDegreeSubmit'])
	{
		// Validate Form Data
		$dbData = array();
		
		// 1. Check Degree Name
		$dbData['name'] = ucwords(strtolower(parseStr($_POST['name'])));
		if (isblank($dbData['name']))
        {
            $errors[] = "Degree name must not be empty.";
        }
		
		// 2. Check Degree Code
		$dbData['code'] = strtoupper(parseStr($_POST['code']));
		if (strlen($dbData['code']) <= 0 || strlen($dbData['code']) > 4)
        {
            $errors[] = "Degree code must be between one and four characters.";
        }
		
		
		// Submit New Degree to Database
		if (count($errors) == 0)
		{
			// 1. Prepare Data for Query
			$assigns = array();
			$params = array();
			
			// 1.1. Date Created
			$assigns[] = "`dateCreated` = NOW()";
			
			// 1.2. Department
			$assigns[] = "`fkDepartment` = :fkDepartment";
			$params[':fkDepartment'] = $_SESSION['fkDepartment'];
			
			// 1.3. Variables in $dbData
			foreach ($dbData as $key => $value)
			{
				$assigns[] = "`$key` = :$key";
				$params[':' . $key] = $value;
			}
			
			// 1.4. Implode Assignment Variable
			$assigns = implode(', ', $assigns);
			
			
			// 2. Send Query
			$stmt = $pdo->prepare("INSERT INTO `Degree` SET $assigns");
			$success = $stmt->execute($params);
			
			
			// 3. Check for Errors
			if (!$success)
			{
				// Duplicate Degree
				if (strcmp($stmt->errorCode(), '23000') == 0)
                {
                    $errors[] = "Degree already exists in database.";
                }
				
				// Unknown Error
				else
                {
                    $errors[] = "Unknown database error occurred.";
                }
			}
			
			
			// 4. On Success, Refresh Page To Reflect New Degree
			else
            {
                redirect('degreesManage.php');
            }
		}
	}
	
	
	// Populate Form Data With Correct Values
	$PAGEDATA = array();
	$PAGEDATA['name'] = ucwords(strtolower(parseStr($_POST['name'])));
	$PAGEDATA['code'] = strtoupper(parseStr($_POST['code']));
	
?>




<!DOCTYPE html>
<html lang="en">
	<head>
		<!-- Title and Icon -->
		<title>eAssess CCU - Add Degree</title>
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
				<h2 class="title">Add Degree</h2>
				
				<?php outputErrorsHTML($errors); ?>
					
				<form name="addDegree" action="" method="POST">
					<table class="formTable padded widthFull">
						<tr>
							<th>Name</th>
							<td><input name="name" type="text" value="<?php echo $PAGEDATA['name']; ?>" autofocus /></td>
						</tr>
						<tr>
							<th>Code (Nickname)</th>
							<td><input name="code" type="text" maxlength="4" value="<?php echo $PAGEDATA['code']; ?>" /></td>
						</tr>
						<tr>
							<td colspan="2"><input name="addDegreeSubmit" type="submit" value="Add Degree" /><input name="addDegreeCancel" type="submit" value="Cancel" /></td>
						</tr>
					</table>
				</form>
			</section>
			
			<?php require('html/footer.php'); ?>
		</div>
	</body>
</html>