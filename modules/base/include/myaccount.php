<?php

function displayRoles($db){
	
	$id = $_SESSION["userinfo"]["id"];

	$querystatement="SELECT roles.id,roles.name
					FROM roles INNER JOIN rolestousers ON rolestousers.roleid=roles.id 
					WHERE rolestousers.userid=".$id;
	$assignedquery=$db->query($querystatement);
	while($therecord=$db->fetchArray($assignedquery))
		echo "<li>".$therecord["name"]."</li>";
}

function changePassword($variables,$id,$db){
	if(DEMO_ENABLED=="false"){
		$querystatement="SELECT password FROM users WHERE id=".$id;
		$queryresult=$db->query($querystatement);
		if($queryresult && $db->numRows($queryresult)){
			$userrecord=$db->fetchArray($queryresult);
			if(!password_verify($variables["curPass"], $userrecord["password"]))
				return "Current Password Incorrect";
			$newhash = password_hash($variables["newPass"], PASSWORD_DEFAULT);
			$querystatement="UPDATE users SET password='".mysql_real_escape_string($newhash)."' WHERE id=".$id;
			$db->query($querystatement);
			return "Password Updated";
		} else
			return "Current Password Incorrect";
	} else
		return "Changing password is disbabled in demonstration mode.";
}

function updateContact($variables,$id,$db){
	$querystatement="UPDATE users SET email=\"".$variables["email"]."\", phone=\"".$variables["phone"]."\" WHERE id=".$id;
	$queryresult=$db->query($querystatement);
	$_SESSION["userinfo"]["email"]=$variables["email"];
	$_SESSION["userinfo"]["phone"]=$variables["phone"];
	return "Contact Information Updated";
}


if(isset($_POST["command"]))
	switch($_POST["command"]){
		case "Change Password":
			$statusmessage=changePassword(addSlashesToArray($_POST),$_SESSION["userinfo"]["id"],$db);
		break;
		case "Update Contact":
			$statusmessage=updateContact(addSlashesToArray($_POST),$_SESSION["userinfo"]["id"],$db);
		break;
		default:
			$statusmessage="\"".$_POST["command"]."\"";
		break;
	}
?>