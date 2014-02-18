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
	if (isset($_POST['addSLOCancel']) && $_POST['addSLOCancel'])
    {
        redirect('SLOsManage.php');
    }
	
	
	// Handle Submit Button
	if (isset($_POST['addSLOSubmit']) && $_POST['addSLOSubmit'])
	{
		// Validate Form Data
		$dbData = array();
		
		// 1. Check SLO Code
		$dbData['code'] = strtoupper(parseStr($_POST['code']));
		if (isblank($dbData['code']))
        {
            $errors[] = "SLO code must not be empty.";
        }
		
		// 2. Check SLO Description
		$dbData['description'] = ucfirst(parseStr($_POST['description']));
		if (isblank($dbData['description']))
        {
            $errors[] = "SLO description must not be empty.";
        }
		
		// 3. Check SLO Degrees
		if (!isset($_POST['degree']) || count($_POST['degree']) == 0)
        {
            $errors[] = "At least one degree must be selected.";
        }
		
		
		// Submit New SLO to Database
		if (count($errors) == 0)
		{
			// 1. Prepare Data for Query
			$assigns = array();
			$params = array();
			
			// 1.1. Date Created
			$assigns[] = "`dateCreated` = NOW()";
			
			// 1.2. Variables in $dbData
			foreach ($dbData as $key => $value)
			{
				$assigns[] = "`$key` = :$key";
				$params[':' . $key] = $value;
			}
			
			// 1.3. Implode Assignment Variable
			$assigns = implode(', ', $assigns);
			
			
			// 2. Send Query
			$stmt = $pdo->prepare("INSERT INTO `SLO` SET $assigns");
			$success = $stmt->execute($params);
			
			
			// 3. Check for Errors
			if (!$success)
            {
                $errors[] = "Unknown database error occurred.";
            }
			
			
			// 4. Add Degree-SLO Mappings
			else if (isset($_POST['degree']))
			{
				$fkSLO = intval($pdo->lastInsertId());
				foreach($_POST['degree'] as $idDegree)
				{
					$stmt = $pdo->prepare("INSERT INTO `SLOXDegree` SET `fkDegree` = :fkDegree, `fkSLO` = :fkSLO");
					$stmt->bindValue(':fkDegree', $idDegree);
					$stmt->bindValue(':fkSLO', $fkSLO);
					$success = $stmt->execute();
					if (!$success)
                    {
                        $errors[] = "Internal database error: Could not add new SLO-Degree mapping.";
                    }
				}
			}
			
			
			// 5. On Success, Refresh Page To Reflect New SLO
			if (count($errors) == 0)
            {
                redirect('SLOsManage.php');
            }
		}
	}
	
	
	// Populate Form Data With Correct Values
	$PAGEDATA = array();
	$PAGEDATA['code'] = strtoupper(parseStr($_POST['code']));
	$PAGEDATA['description'] = ucfirst(parseStr($_POST['description']));
	
	// 1. Retrieve List of Degrees From Database
	$query = "
		SELECT `ID`, `name`, `code`
		FROM `Degree`
		WHERE `Degree`.`status` = 1 AND `Degree`.`fkDepartment` = :fkDepartment
		ORDER BY `Degree`.`name`
	";
	$stmt = $pdo->prepare($query);
	$stmt->bindValue(':fkDepartment', $_SESSION['fkDepartment']);
	$success = $stmt->execute();
	if (!$success || ($degrees = $stmt->fetchAll(PDO::FETCH_ASSOC)) === FALSE)
    {
        redirect('SLOsManage.php');
    }
	
	// 2. Handle Degree Checkboxes
	foreach($degrees as &$degree)
    {
        $degree['SLO'] = (isset($_POST['degree']) && in_array($degree['ID'], $_POST['degree'])) ? 1 : 0;
    }
	unset($degree);
	
?>




<!DOCTYPE html>
<html lang="en">
	<head>
		<!-- Title and Icon -->
		<title>eAssess CCU - Add SLO</title>
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
				<h2 class="title">Add SLO</h2>
				
				<?php outputErrorsHTML($errors); ?>
					
				<form name="addSLO" action="" method="POST">
					<table class="formTable padded widthFull">
						<tr>
							<th>Code</th>
							<td><input name="code" type="text" value="<?php echo $PAGEDATA['code']; ?>" autofocus /></td>
						</tr>
						<tr>
							<th>Description</th>
							<td><textarea name="description"><?php echo $PAGEDATA['description']; ?></textarea></td>
						</tr>
						<tr>
							<th>Degrees</th>
							<td>
								<?php
									foreach($degrees as $degree)
									{
										echo '<label>';
										echo '<input type="checkbox" name="degree[]" value="' . $degree['ID'] . '"';
										if ($degree['SLO']) echo ' checked ';
										echo '>' . $degree['name'] . ' (' . $degree['code'] . ')';
										echo '</label><br />';
									}
									unset($degree);
								?>
							</td>
						</tr>
						<tr>
							<td colspan="2"><input name="addSLOSubmit" type="submit" value="Add SLO" /><input name="addSLOCancel" type="submit" value="Cancel" /></td>
						</tr>
					</table>
				</form>
			</section>
			
			<?php require('html/footer.php'); ?>
		</div>
	</body>
</html>