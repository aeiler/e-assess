<?php
	
	// Include Utilities PHP File
	require_once('_utilities.php');
	
	
	// Initialize Error Array
	$errors = array();
	
	
	// Start Session
	session_start();
	
	
	// Connect To Database
	$pdo = dbConnect();
	
	
	// Require User Login
	requireLogin();
	
	
	// Query Database For User's Info
	$query = "
		SELECT
			`firstName`,
			`lastName`,
			`username`,
			`passwordHash`
		FROM `User`
		WHERE `ID` = :ID
		LIMIT 1
	";
	$stmt = $pdo->prepare($query);
	$stmt->bindValue(':ID', $_SESSION['ID']);
	$success = $stmt->execute();
	if (!$success || ($userInfo = $stmt->fetch(PDO::FETCH_ASSOC)) === FALSE) redirect('index.php');
	
	
	// Handle Cancel Button
	if (isset($_POST['editUserCancel']) && $_POST['editUserCancel']) redirect('index.php');
	
	// Handle Submit Button
	if (isset($_POST['editUserSubmit']) && $_POST['editUserSubmit'])
	{
		// Validate Form Data
		$dbData = array();
		
		// 1. Check First Name
		$dbData['firstName'] = parseStr($_POST['firstName']);
		if (isblank($dbData['firstName'])) $errors[] = "First name must not be empty.";
		
		// 2. Check Last Name
		$dbData['lastName'] = parseStr($_POST['lastName']);
		if (isblank($dbData['lastName'])) $errors[] = "Last name must not be empty.";
		
		// 3. Check Username
		$dbData['username'] = parseStr($_POST['username']);
		if (strlen($dbData['username']) < 2 || strlen($dbData['username']) > 10)
			$errors[] = "Invalid username. Username must be at least 2 characters and no more than 10 characters.";
		
		// 4. Check New Passwords
		if (isset($_POST['password1']) && isset($_POST['password2']) && (!isblank($_POST['password1']) || !isblank($_POST['password2'])))
		{
			// 4.1. Check Passwords for Match
			if (strcmp($_POST['password1'], $_POST['password2']) != 0) $errors[] = "Passwords do not match.";
			
			// 4.2. Check Password for Security
			else if (strlen($_POST['password1']) < 8 || preg_match('#\d#', $_POST['password1']) != 1)
				$errors[] = "Invalid password. Password must be at least 8 characters long and contain at least one digit.";
		
			// 4.3. Hash Password
			else $params['password'] = hasher(parseStr($_POST['password1']));
		}
		
		// 5. Check Existing Password
		// 5.1. Check Presence of Password
		if (isblank($_POST['password'])) $errors[] = "Current password must not be empty.";
		
		// 5.2. Check Password for Security
		else if (strlen($_POST['password']) < 8 || preg_match('#\d#', $_POST['password']) != 1)
			$errors[] = "Invalid current password. Password must be at least 8 characters long and contain at least one digit.";
		
		// 5.3. Check Password for Correctness
		else if (!hasher(parseStr($_POST['password']), $userInfo['passwordHash']))
			$errors[] = "Incorrect current password.";
		
		
		// Submit User Changes to Database
		if (count($errors) == 0)
		{
			// 1. Prepare Data for Query
			$assigns = array();
			$params = array();
			
			// 1.1. ID
			$params[':ID'] = $_SESSION['ID'];
			
			// 1.2. Variables in $dbData
			foreach ($dbData as $key => $value)
			{
				$assigns[] = "`$key` = :$key";
				$params[':' . $key] = $value;
			}
			
			// 1.3. Implode Assignment Variable
			$assigns = implode(', ', $assigns);
			
			
			// 2. Send Query
			$stmt = $pdo->prepare("UPDATE `User` SET $assigns WHERE `ID` = :ID");
			$success = $stmt->execute($params);
			
			
			// 3. Check for Errors
			if (!$success)
			{
				// Duplicate Username
				if (strcmp($pdo->errorCode(), '23000') == 0)
					$errors[] = "Username already exists in database.";
				
				// Unknown Error
				// NOTE: FOR SOME REASON, IF USERNAME ALREADY EXISTS IN DATABASE, MYSQL FAILS BUT DOES NOT
				//     OUTPUT AN ERROR. THUS, THE IF STATEMENT ABOVE WILL NEVER EXECUTE (ALTHOUGH IT SHOULD).
				//     AS A WORKAROUND, IF MYSQL FAILS AT ALL, WE WILL ASSUME THAT IT IS BECAUSE THE USERNAME
				//     ALREADY EXISTS IN THE DATABASE.
				else $errors[] = "Username already exists in database.";
			}
			
			// 4. Success - Refresh Page To Reflect Edits
			else redirect();
		}
	}
	
	
	// Populate Form Data With Correct Values
	// 1. Create and Populate $PAGEDATA Associative Array To Hold Old Form Data
	$PAGEDATA = array();
	$PAGEDATA['firstName'] = parseStr($_POST['firstName']);
	$PAGEDATA['lastName'] = parseStr($_POST['lastName']);
	$PAGEDATA['username'] = parseStr($_POST['username']);
	$PAGEDATA['password1'] = parseStr($_POST['password1']);
	
	// 2. If No Old Form Data Exists, Use Database For Current Data
	if (!isset($_POST['editUserSubmit']) || !$_POST['editUserSubmit'])
	{
		$PAGEDATA['firstName'] = $userInfo['firstName'];
		$PAGEDATA['lastName'] = $userInfo['lastName'];
		$PAGEDATA['username'] = $userInfo['username'];
	}
	
?>



<!DOCTYPE html>
<html lang="en">
	<head>
		<!-- Title and Icon -->
		<title>eAssess CCU - Edit User</title>
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
			<section id="editUser" class="bgField shadow corner center">
				<h2 class="title">Edit User</h2>
				
				<?php outputErrorsHTML($errors); ?>
				
				<form name="editUser" action="" method="POST">
					<table class="formTable padded widthFull">
						<tr>
							<th>First Name</th>
							<td><input name="firstName" type="text" value="<?php echo $PAGEDATA['firstName']; ?>" /></td>
						</tr>
						<tr>
							<th>Last Name</th>
							<td><input name="lastName" type="text" value="<?php echo $PAGEDATA['lastName']; ?>" /></td>
						</tr>
						<tr>
							<th>Username</th>
							<td><input name="username" type="text" value="<?php echo $PAGEDATA['username']; ?>" /></td>
						</tr>
                        <tr>
                            <th>Current Password</th>
                            <td><input name="password" type="password" autofocus /></td>
                        </tr>
						<tr>
							<th>New Password</th>
							<td><input name="password1" type="password" value="<?php echo $PAGEDATA['password1']; ?>" /></td>
						</tr>
						<tr>
							<th>Confirm New Password</th>
							<td><input name="password2" type="password" /></td>
						</tr>
						<tr>
							<td colspan="2">
								<div>*Password must be at least 8 characters with at least one digit.</div>
								<input name="editUserSubmit" type="submit" value="Save Changes" />
								<input name="editUserCancel" type="submit" value="Cancel" />
							</td>
						</tr>
					</table>
				</form>
			</section>
			
			<?php require('html/footer.php'); ?>
		</div>
	</body>
</html>