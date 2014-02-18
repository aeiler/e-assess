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
	
	
	// Require Provided Degree ID
	// 1. Attempt to Query Database for Supplied Degree's Info
	$query = "
		SELECT
			`ID`,
			`name`,
			`code`
		FROM `Degree`
		WHERE `Degree`.`ID` = :ID AND `Degree`.`fkDepartment` = :fkDepartment
		LIMIT 1
	";
	$stmt = $pdo->prepare($query);
	$stmt->bindValue(':ID', intval($_REQUEST['idDegree']));
	$stmt->bindValue(':fkDepartment', $_SESSION['fkDepartment']);
	$success = $stmt->execute();
	
	// 2. Ensure a Valid Degree Was Found in the Database
	if (!$success || ($degreeInfo = $stmt->fetch(PDO::FETCH_ASSOC)) === FALSE)
    {
        redirect('degreesManage.php');
    }

	// Handle Cancel Button
	if (isset($_POST['editDegreeCancel']) && $_POST['editDegreeCancel'])
    {
        redirect('degreesManage.php');
    }
	
	// Handle Submit Button
	if (isset($_POST['editDegreeSubmit']) && $_POST['editDegreeSubmit'])
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
		
		
		// Submit Degree Changes to Database
		if (count($errors) == 0)
		{
			// 1. Prepare Data for Query
			$assigns = array();
			$params = array();
			
			// 1.1. Degree ID
			$params[':ID'] = $degreeInfo['ID'];
			
			// 1.2. Variables in $dbData
			foreach ($dbData as $key => $value)
			{
				$assigns[] = "`$key` = :$key";
				$params[':' . $key] = $value;
			}
			
			// 1.3. Implode Assignment Variable
			$assigns = implode(', ', $assigns);
			
			
			// 2. Send Query
			$stmt = $pdo->prepare("UPDATE `Degree` SET $assigns WHERE `ID` = :ID");
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
			
			
			// 4. On Success, Refresh Page To Reflect Degree Changes
			else
            {
                redirect('degreesManage.php');
            }
		}
	}


	// Populate Form Data With Correct Values
	$PAGEDATA = array();
	// 1. Use Old Form Data Form Current Data, If Available
	if (isset($_POST['editDegreeSubmit']) && $_POST['editDegreeSubmit'])
	{
		$PAGEDATA['name'] = ucwords(strtolower(parseStr($_POST['name'])));
		$PAGEDATA['code'] = strtoupper(parseStr($_POST['code']));
	}
	
	// 2. If No Old Form Data Exists, Use Database For Current Data
	else
	{
		$PAGEDATA['name'] = ucwords(strtolower($degreeInfo['name']));
		$PAGEDATA['code'] = strtoupper($degreeInfo['code']);
	}
?>




<!DOCTYPE html>
<html lang="en">
	<head>
		<!-- Title and Icon -->
		<title>eAssess CCU - Edit Degree</title>
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
				<h2 class="title">Edit Degree</h2>
				
				<?php outputErrorsHTML($errors); ?>
					
				<form name="editDegree" action="" method="POST">
					<table class="formTable padded widthFull">
						<tr>
							<th>Name</th>
							<td><input name="name" type="text" value="<?php echo $PAGEDATA['name']; ?>" /></td>
						</tr>
						<tr>
							<th>Code (Nickname)</th>
							<td><input name="code" type="text" maxlength="4" value="<?php echo $PAGEDATA['code']; ?>" /></td>
						</tr>
						<tr>
							<td colspan="2"><input name="editDegreeSubmit" type="submit" value="Save Changes" /><input name="editDegreeCancel" type="submit" value="Cancel" /></td>
						</tr>
					</table>
					
					<input name="idDegree" type="hidden" value="<?php echo $degreeInfo['ID']; ?>" />
				</form>
			</section>
			
			<?php require('html/footer.php'); ?>
		</div>
	</body>
</html>