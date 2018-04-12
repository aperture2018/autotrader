<?PHP
class Auth {
	public function checkUser() {
	    if (isset($_POST["username"]) && isset($_POST["password"])) {
		    $_SESSION["username"] = $_POST["username"];
			$_SESSION["password"] = $_POST["password"];
		}
		if (!isset($_SESSION["username"]) || !isset($_SESSION["password"])) {
		    $this->authForm();
			exit();
		}
		$checked = password_verify($_SESSION["password"], ADMIN_PASSWORD);
	    if (!$checked || $_SESSION["username"] != ADMIN_USERNAME) {
			$this->authForm();
			exit();
		}
	}
	protected function authForm() {
		echo '
		<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
		<html><head><title></title><style>INPUT {margin: 5px;}</style></head><body>
		<div align="center" style="margin-top:200px;">
		<form action="" method="post">
		<input type = "text" name = "username" size="16" value=""><br>
		<input type = "text" name = "password" size="16" value="" type = "password"><br>
		<input type = "submit" name = "submit" value = "Submit">
		</form>
		</div></body></html>
		';
	}
}
?>