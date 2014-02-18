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
	
	
	
	
	// Require Provided SLO ID
	$query = "
		SELECT DISTINCT
			`SLO`.`ID` AS ID,
			`SLO`.`code`,
			`SLO`.`description`,
			`SLO`.`status`
		FROM `SLO`
		JOIN `SLOXDegree`
		ON `SLOXDegree`.`fkSLO` = `SLO`.`ID`
		JOIN `Degree`
		ON `Degree`.`ID` = `SLOXDegree`.`fkDegree`
		WHERE `SLO`.`ID` = :ID AND `Degree`.`fkDepartment` = :fkDepartment
		LIMIT 1
	";
	$stmt = $pdo->prepare($query);
	$stmt->bindValue(':ID', parseInt($_REQUEST['idSLO']));
	$stmt->bindValue(':fkDepartment', $_SESSION['fkDepartment']);
	$success = $stmt->execute();
		
	if (!$success || ($SLOInfo = $stmt->fetch(PDO::FETCH_ASSOC)) === FALSE)
	{
		redirect('SLOsManage.php');
	}
	
	
	
	
	// Retrieve List of Degrees From Database
	$query = "
		SELECT
			`Degree`.`ID`,
			`Degree`.`name`,
			`Degree`.`code`,
			IF(`SLOXDegree`.`fkSLO` IS NULL, 0, 1) AS SLO
		FROM `Degree`
		LEFT JOIN `SLOXDegree`
		ON `SLOXDegree`.`fkSLO` = :fkSLO AND `SLOXDegree`.`fkDegree` = `Degree`.`ID`
		WHERE
			`Degree`.`status` = 1 AND
			`Degree`.`fkDepartment` = :fkDepartment
		ORDER BY `Degree`.`name`
	";
	$stmt = $pdo->prepare($query);
	$stmt->bindValue(':fkSLO', $SLOInfo['ID']);
	$stmt->bindValue(':fkDepartment', $_SESSION['fkDepartment']);
	$success = $stmt->execute();
	if (!$success || ($degrees = $stmt->fetchAll(PDO::FETCH_ASSOC)) === FALSE)
	{
		redirect('SLOsManage.php');
	}
	
	
	
	
	// Handle Cancel Button
	if (isset($_POST['editSLOCancel']) && $_POST['editSLOCancel'])
    {
        redirect('SLOsManage.php');
    }
	
	
	// Handle Submit Button
	if (isset($_POST['editSLOSubmit']) && $_POST['editSLOSubmit'])
	{
		// Validate Form Data
		$dbData = array();
		
		// 1. Check SLO Code
		$dbData['code'] = strtoupper(parseStr($_POST['code']));
		if (isblank($dbData['code'])) $errors[] = "SLO code must not be empty.";
		
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
		
		
		// Submit SLO Changes to Database
		if (count($errors) == 0)
		{
			// 1. Prepare Data for Query
			$assigns = array();
			$params = array();
			
			// 1.1. SLO ID
			$params[':ID'] = $SLOInfo['ID'];
			
			// 1.2. Variables in $dbData
			foreach ($dbData as $key => $value)
			{
				$assigns[] = "`$key` = :$key";
				$params[':' . $key] = $value;
			}
			
			// 1.3. Implode Assignment Variable
			$assigns = implode(', ', $assigns);
			
			
			// 2. Send Query
			$stmt = $pdo->prepare("UPDATE `SLO` SET $assigns WHERE `ID` = :ID");
			$success = $stmt->execute($params);
			
			
			// 3. Check for Errors
			if (!$success)
            {
                $errors[] = "Unknown database error occurred.";
            }
			
			
			// 4. Update Degree-SLO Mappings
			else
			{
				foreach ($degrees as $degree)
				{
					$oldMap = intval($degree['SLO']);
					$newMap = (isset($_POST['degree']) && in_array($degree['ID'], $_POST['degree']));
					
					if ($oldMap && !$newMap)
					{
						$stmt = $pdo->prepare("DELETE FROM `SLOXDegree` WHERE `fkSLO` = :fkSLO AND `fkDegree` = :fkDegree");
						$stmt->bindValue(':fkSLO', $SLOInfo['ID']);
						$stmt->bindValue(':fkDegree', $degree['ID']);
						$success = $stmt->execute();
						if (!$success) $errors[] = "Internal database error: Could not delete old SLO-Degree mapping.";
					}
					else if (!$oldMap && $newMap)
					{
						$stmt = $pdo->prepare("INSERT INTO `SLOXDegree` SET `fkDegree` = :fkDegree, `fkSLO` = :fkSLO");
						$stmt->bindValue(':fkSLO', $SLOInfo['ID']);
						$stmt->bindValue(':fkDegree', $degree['ID']);
						$success = $stmt->execute();
						if (!$success) $errors[] = "Internal database error: Could not add new SLO-Degree mapping.";
					}
				}
				unset($degree);
				unset($oldMap);
				unset($newMap);
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
	
	// 2. Copy Info From Either Database Or Previously Submitted Form Into Form Fields
	if (isset($_POST['editSLOSubmit']) && $_POST['editSLOSubmit'])
	{
		$PAGEDATA['code'] = strtoupper(parseStr($_POST['code']));
		$PAGEDATA['description'] = ucfirst(parseStr($_POST['description']));
		
		// Handle Degree Checkboxes
		foreach($degrees as &$degree)
        {
            $degree['SLO'] = (isset($_POST['degree']) && in_array($degree['ID'], $_POST['degree'])) ? 1 : 0;
        }
		unset($degree);
	}
	else
	{
		$PAGEDATA['code'] = strtoupper($SLOInfo['code']);
		$PAGEDATA['description'] = ucfirst($SLOInfo['description']);
	}
	
?>




<!DOCTYPE html>
<html lang="en">
	<head>
		<!-- Title and Icon -->
		<title>eAssess CCU - Edit SLO</title>
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
				<h2 class="title">Edit SLO</h2>
				
				<?php outputErrorsHTML($errors); ?>
					
				<form name="editSLOs" action="" method="POST">
					<table class="formTable padded widthFull">
						<tr>
							<th>Code</th>
							<td><input name="code" type="text" value="<?php echo $PAGEDATA['code']; ?>" /></td>
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
							<td colspan="2"><input name="editSLOSubmit" type="submit" value="Save Changes" /><input name="editSLOCancel" type="submit" value="Cancel" /></td>
						</tr>
					</table>
					
					<input name="idSLO" type="hidden" value="<?php echo $SLOInfo['ID']; ?>" />
				</form>
			</section>
			
			<?php require('html/footer.php'); ?>
		</div>
	</body>
</html>