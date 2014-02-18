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
	
	
	// Require Provided Department ID
	// 1. Attempt to Query Database for Department Info
	$stmt = $pdo->prepare("SELECT `name`, `ID` FROM `Department` WHERE `ID` = :ID LIMIT 1");
	$stmt->bindValue(':ID', parseInt($_REQUEST['idDepartment']));
	$success = $stmt->execute();
	
	// 2. Ensure a Valid Department Was Found in the Database
	if (!$success || ($deptInfo = $stmt->fetch(PDO::FETCH_ASSOC)) === FALSE)
    {
        redirect('departmentsManage.php');
    }
	
	
	// Handle Cancel Button
	if (isset($_POST['editDepartmentCancel']) && $_POST['editDepartmentCancel'])
    {
        redirect('departmentsManage.php');
    }
	
	
	// Handle Submit Button
	if (isset($_POST['editDepartmentSubmit']) && $_POST['editDepartmentSubmit'])
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
			$stmt = $pdo->prepare("UPDATE `Department` SET `name` = :name WHERE `ID` = :ID");
			$stmt->bindValue(':name', ucfirst(strtolower(parseStr($_POST['name']))));
			$stmt->bindValue(':ID', $deptInfo['ID']);
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
                redirect();
            }
		}
	}
	
	
	// Populate Form Data With Correct Values
	// 1. Create and Populate $PAGEDATA Associative Array To Hold Old Form Data
	$PAGEDATA = array();
	$PAGEDATA['name'] = ucfirst(strtolower(parseStr($_POST['name'])));
	
	// 2. If No Old Form Data Exists, Use Database For Current Data
	if (!isset($_POST['editDepartmentSubmit']) || !$_POST['editDepartmentSubmit'])
    {
        $PAGEDATA['name'] = $deptInfo['name'];
    }
?>




<!DOCTYPE html>
<html lang="en">
	<head>
		<!-- Title and Icon -->
		<title>eAssess CCU - Edit Department</title>
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
				<h2 class="title">Edit Department</h2>
				
				<?php outputErrorsHTML($errors); ?>
					
				<form name="editDepartment" action="" method="POST">
					<table class="formTable padded widthFull">
						<tr>
							<th>Department Name</th>
							<td><input name="name" type="text" value="<?php echo $PAGEDATA['name']; ?>" /></td>
						</tr>
						<tr>
							<td colspan="2"><input name="editDepartmentSubmit" type="submit" value="Save Changes" /><input name="editDepartmentCancel" type="submit" value="Cancel" /></td>
						</tr>
					</table>
					
					<input name="idDepartment" type="hidden" value="<?php echo $deptInfo['ID']; ?>" />
				</form>
			</section>
			
			<?php require('html/footer.php'); ?>
		</div>
	</body>
</html>