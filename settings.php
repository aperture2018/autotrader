<?PHP
require 'config.inc.php';
session_start();
$auth = new Auth();
$auth->checkUser();

//Update db if there is POST data
if (isset($_POST["update"]))
{
    $db->autocommit(FALSE);
	foreach($_POST as $setting => $value)
	{
		$statement = $db->prepare('UPDATE settings SET Value = ? WHERE Setting = ?');
		$statement->bind_param('ss', $value, $setting);
	    $statement->execute();
		$statement->close();
	}
	$db->commit();
}


//Get settings
$results = $db->query('SELECT * FROM settings');
while ($row = $results->fetch_assoc())
{
    $settings[$row["Setting"]] = $row["Value"];
}

//Trading mode
$mode = $settings["mode"];
$updateFrequency = $settings["updateFrequency"];
$pumpMonitorActive = $settings["pumpMonitorActive"];
//Buy settings
$buyActive = $settings["buyActive"];
$buyNow = $settings["buyNow"];
$blackList = $settings["blackList"];
$maxCoins = $settings["maxCoins"];
$amount = $settings["amount"];
$maxBitcoinPriceChange = $settings["maxBitcoinPriceChange"];
$maxCoinPriceChange = $settings["maxCoinPriceChange"];
$minCoinPriceChange = $settings["minCoinPriceChange"];
$coinCooldown = $settings["coinCooldown"];
//Sell settings
$sellActive = $settings["sellActive"];
$stopLoss = $settings["stopLoss"];
$dropDeviation = $settings["dropDeviation"];
$minProfit = $settings["minProfit"];
$minBaseVolume = $settings["minBaseVolume"];
$maxSpread = $settings["maxSpread"];
$maxNegativeCoins = $settings["maxNegativeCoins"];
$buyAlgo = $settings["buyAlgo"];
$maxBelowTsCoins = $settings["maxBelowTsCoins"];
$minThreshold = $settings["minThreshold"];
$pumpMonUpdateFrequency = $settings["pumpMonUpdateFrequency"];
$pumpMonPriceChange = $settings["pumpMonPriceChange"];
$pumpMonVolumeChange = $settings["pumpMonVolumeChange"];
$bittrexPublicKey = $settings["bittrexPublicKey"];
$bittrexPrivateKey = $settings["bittrexPrivateKey"];
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">

<html>
<head>
	<title></title>
</head>
<style>
.content {
width:800px;
margin:0 auto;
margin-top: 100px;
font-family: Verdana, Arial;
}
.market {
width: 200px;
padding: 10px;
display: inline-block;
}
.header {
width: 600px;
padding: 10px;
clear: both;
font-family: Verdana, Arial;
font-size: 26px;
}
.sold {
width: 800px;
padding: 10px;
font-family: Verdana, Arial;
font-size: 16px;
}
.hold {
font-family: Verdana, Arial;
font-size: 10px;
color: Gray;
}
.menu {
width: 600px;
padding: 10px;
clear: both;
font-family: Verdana, Arial;
font-size: 16px;
}
.message {
display: table;
margin: 10px;
padding-left: 5px;
padding-right: 5px;
border: 1px solid #e7e7e7;
border-radius: 10px;
background-color: LightGreen;
animation-duration: 2s;
animation-timing-function: ease-out;
animation-delay: 0s;
animation-iteration-count: 1;
animation-name: message;
opacity: 0;
}
@keyframes message {
    0% {
        opacity: 1;
    }
    100% {
        opacity: 0;
    }
}
A {
text-decoration: none;
color: Black;
}
A:hover {
text-decoration: underline;
}
FORM {
width: 600px;
font-family: Verdana, Arial;
font-size: 12px;
}
INPUT, TEXTAREA {
margin-top: 5px;
margin-bottom: 5px;
}
</style>

<body>

<div class="content">
<div class="header">Settings</div>
<div class="menu"><a href="index.php">Back</a></div>

<?PHP if (isset($_POST["update"])) {echo '<div class="message">Settings updated</div>';} ?>

<form action="" method="post">
  <fieldset>
   <legend>Trading mode:</legend>
   <input type="radio" name="mode" value="live" <?PHP if ($mode == "live") {echo "checked";} ?>> live
   <input type="radio" name="mode" value="simulation" <?PHP if ($mode == "simulation") {echo "checked";} ?>> simulation
   <input type="radio" name="mode" value="stopall" <?PHP if ($mode == "stopall") {echo "checked";} ?>> stop all<br>
   	Update frequency: <input type = "text" name = "updateFrequency" size="2" value="<?PHP {echo $updateFrequency;} ?>"> sec
  </fieldset>
  <fieldset>
    <legend>Pump monitor:</legend>
	<input type="radio" name="pumpMonitorActive" value="1" <?PHP if ($pumpMonitorActive != 0) {echo "checked";} ?>> active
    <input type="radio" name="pumpMonitorActive" value="0" <?PHP if ($pumpMonitorActive == 0) {echo "checked";} ?>> inactive<br>
	Detection period: <input type = "text" name = "pumpMonUpdateFrequency" size="2" value="<?PHP {echo $pumpMonUpdateFrequency;} ?>"> sec<br>
	Minimum price change:
	<input type = "text" name = "pumpMonPriceChange" size="2" value="<?PHP {echo $pumpMonPriceChange;} ?>"> percent<br>
	Minimum volume change:
	<input type = "text" name = "pumpMonVolumeChange" size="2" value="<?PHP {echo $pumpMonVolumeChange;} ?>"> percent<br>
  </fieldset>
  <fieldset>
    <legend>Buying options:</legend>
    Buying:<br>
    <input type="radio" name="buyActive" value="1" <?PHP if ($buyActive != 0) {echo "checked";} ?>> active
    <input type="radio" name="buyActive" value="0" <?PHP if ($buyActive == 0) {echo "checked";} ?>> inactive<br>
    Blacklist:<br>
    <textarea rows = "2" cols = "40" name = "blackList"><?PHP {echo $blackList;} ?></textarea><br>
	Max coins to hold:
	<input type = "text" name = "maxCoins" size="2" value="<?PHP {echo $maxCoins;} ?>"><br>
	BTC amount to spend on each coin:
	<input type = "text" name = "amount" size="4" value="<?PHP {echo $amount;} ?>"><br>
	Stop buying if 
	<input type = "text" name = "maxNegativeCoins" size="2" value="<?PHP {echo $maxNegativeCoins;} ?>"> coins show negative profit<br>
	Stop buying if <input type = "text" name = "maxBelowTsCoins" size="2" value="<?PHP {echo $maxBelowTsCoins;} ?>"> coins are below
	<input type = "text" name = "minThreshold" size="2" value="<?PHP {echo $minThreshold;} ?>"> percent<br>
	Algorithm: 
	<input type="radio" name="buyAlgo" value="random" <?PHP if ($buyAlgo == "random") {echo "checked";} ?>> Random 
    <input type="radio" name="buyAlgo" value="sort_by_volume" <?PHP if ($buyAlgo == "sort_by_volume") {echo "checked";} ?>> Sort by volume<br>
	Maximum coin price change (%):
	<input type = "text" name = "maxCoinPriceChange" size="4" value="<?PHP {echo $maxCoinPriceChange;} ?>"><br>
	Minimum coin price change (%):
	<input type = "text" name = "minCoinPriceChange" size="4" value="<?PHP {echo $minCoinPriceChange;} ?>"><br>
	Minimum base volume (BTC):
	<input type = "text" name = "minBaseVolume" size="4" value="<?PHP {echo $minBaseVolume;} ?>"><br>
	Maximum spread (%):
	<input type = "text" name = "maxSpread" size="4" value="<?PHP {echo $maxSpread;} ?>"><br>
	Coin cooldown (sec):
	<input type = "text" name = "coinCooldown" size="4" value="<?PHP {echo $coinCooldown;} ?>"><br>
  </fieldset>

  <fieldset>
    <legend>Selling options:</legend>
    Selling:<br>
    <input type="radio" name="sellActive" value="1" <?PHP if ($sellActive != 0) {echo "checked";} ?>> active
    <input type="radio" name="sellActive" value="0" <?PHP if ($sellActive == 0) {echo "checked";} ?>> inactive<br>
	Stop loss price drop (%):
	<input type = "text" name = "stopLoss" size="4" value="<?PHP {echo $stopLoss;} ?>"><br>
	Selling trigger price drop (%):
	<input type = "text" name = "dropDeviation" size="4" value="<?PHP {echo $dropDeviation;} ?>"><br>
	Minimum profit to achieve on each coin (%):
	<input type = "text" name = "minProfit" size="4" value="<?PHP {echo $minProfit;} ?>"><br>
  </fieldset>
  
    <fieldset>
    <legend>Bittrex API keys:</legend>
	Public key:
	<input type = "text" name = "bittrexPublicKey" size="38" value="<?PHP {echo $bittrexPublicKey;} ?>"><br>
	Private key:
	<input type = "text" name = "bittrexPrivateKey" size="37" value="<?PHP {echo $bittrexPrivateKey;} ?>"><br>
  </fieldset>
  
<input type = "hidden" name = "update" value = "1">
<input type = "submit" name = "submit" value = "Save">
</form>