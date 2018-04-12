<?PHP
require 'config.inc.php';
session_start();
$auth = new Auth();
$auth->checkUser();

$startTime = microtime(true);
set_time_limit(360);

//Update coinmarketcap database
$cmcUpd = time() + 3600;
Coinmarketcap::refreshDatabase($cmcUpd);
Cryptocompare::refreshDatabase($cmcUpd);

$message = '';

//Get settings from db
$results = $db->query('SELECT * FROM settings');
while ($row = $results->fetch_assoc()) {
    $settings[$row["Setting"]] = $row["Value"];
}
//Settings
$mode = $settings["mode"];
$amount = $settings["amount"];
$sortBy = $settings["sortBy"];
$sortOrder = $settings["sortOrder"];
$bitcoinPrice = $settings['bitcoinPrice'];

//Accept manual buy request
if (isset($_POST["buyManual"])) {
    if ($_POST["buyMode"] == "auto") {
        buyCoins($_POST["buyManual"], $amount);
		$message = "Bought";
	}
	elseif ($_POST["buyMode"] == "program")	{
	    buyProgram($_POST["buyManual"]);
		$message = "Added";
	}
	//Show message
	foreach ($_POST["buyManual"] as $pdata)	{
	    $pdata_arr = explode(";;", $pdata);
		$coin = $pdata_arr[0];
		$coinInfo = $pdata_arr[1];
	    $message .= " " . substr($coin, 4);
	}
}

//Accept sort options change
if (isset($_POST["sortBy"])) {
    $sortBy = trim($_POST["sortBy"]);
    $db->query('UPDATE settings SET Value = "' . $sortBy . '" WHERE Setting = "sortBy"');
}
if (isset($_POST["sortOrder"])) {
    $sortOrder = trim($_POST["sortOrder"]);
    $db->query('UPDATE settings SET Value = "' . $sortOrder . '" WHERE Setting = "sortOrder"');
}

//Get market summaries
$marketsummaries = array();
$results = $db->query('SELECT * FROM bittrex');
while ($row = $results->fetch_assoc()) {
    $marketsummaries[] = $row;
}

$appMarkets = array();

$coinId = 0;
foreach ($marketsummaries as $marketArray) {
    //echo $marketArray["MarketName"] . "<BR>";
	//echo "Base volume: " . $marketArray["BaseVolume"] . "<BR>";
	
    //Exclude all pairs except BTC
    if (substr($marketArray["MarketName"], 0, 3) != "BTC") {continue;}
	
	//Exclude all coins with base volume below 10 BTC
	//if ($marketArray["BaseVolume"] < 10) {continue;}
	
	//Check that the coin is not already bought in table "coins"
	$check = $db->query('SELECT * FROM coins WHERE marketName = "' . $marketArray["MarketName"] . '" AND sellDate IS NULL');
	$num_rows = $check->num_rows;
	if ($num_rows > 0) {continue;}
	
	//Check that the coin is not already bought in table "program"
	$check = $db->query('SELECT * FROM program WHERE coin = "' . $marketArray["MarketName"] . '" AND sellDate IS NULL');
	$num_rows = $check->num_rows;
	if ($num_rows > 0) {continue;}
	
	//Put coin name into appropriate markets array
	$appMarkets[$coinId]["marketName"] = $marketArray["MarketName"];
    
	//Fetch coin image from cryptocompare.com
	$coinName = substr($marketArray["MarketName"], 4);
    Cryptocompare::fetchCoinImage($coinName);
    
	//Fetch coin info from coinmarketcap database
	$results = $db->query('SELECT * FROM coinmarketcap WHERE symbol = "' . $coinName . '"');
    $cmc = $results->fetch_assoc();
	
	//Set vars
	$bid = $marketArray["Bid"];
	$ask = $marketArray["Ask"];
	if ($bid == 0 || $ask == 0) {continue;}
	$last = $marketArray["Last"];
	$high = $marketArray["High"];
	$low = $marketArray["Low"];
	$volume = $marketArray["Volume"];
	
	$prevDay = $marketArray["PrevDay"];
	$appMarkets[$coinId]["baseVolume"] = $baseVolume = round($marketArray["BaseVolume"], 1);
	$appMarkets[$coinId]["openBuyOrders"] = $marketArray["OpenBuyOrders"];
    $appMarkets[$coinId]["openSellOrders"] = $marketArray["OpenSellOrders"];
	
	//Current price
	$appMarkets[$coinId]["price"] = $marketArray["Bid"];
	
	//Coin name (CMC)
	$appMarkets[$coinId]["name"] = $cmc["name"];
	
	//Coin id (CMC)
	$appMarkets[$coinId]["cmcId"] = $cmc["id"];
	
	//Percent change 7 days (CMC)
	$appMarkets[$coinId]["percent_change_7d"] = $cmc["percent_change_7d"];
	
	//Calculate spread
	$appMarkets[$coinId]["spread"] = round(100 * (($ask - $bid) / $ask), 2);
	
	//Calculate the gap between 24h high and 24h low
	$appMarkets[$coinId]["priceGap"] = round(100 * (($high - $low) / $low));
	
	//Calculate High to Low ratio
	$appMarkets[$coinId]["highToLowRatio"] = round($high / $low, 2);
	
	//Check whether last price is closer to 24h high or 24h low (overbought level)
	$appMarkets[$coinId]["overBought"] = round(($bid - $low) / (($high - $low) / 100));
	
	//Calculate latest price shift
	$appMarkets[$coinId]["priceShift"] = round(100 * (($bid - $prevDay) / $prevDay), 1);
	
	$coinId++;
	//echo $marketArray["MarketName"] . "<BR>";
	//if ($coinId >= 20) {break;}
}

//Buying logic

//Sort array
$sort = array();
foreach($appMarkets as $k=>$v) {
	$sort['baseVolume'][$k] = $v['baseVolume'];
	$sort['priceShift'][$k] = $v['priceShift'];
	$sort['percent_change_7d'][$k] = $v['percent_change_7d'];
    $sort['spread'][$k] = $v['spread'];
    $sort['openBuyOrders'][$k] = $v['openBuyOrders'];
}
if ($sortOrder == "desc") {
    array_multisort($sort[$sortBy], SORT_DESC, $appMarkets);
} else {
    array_multisort($sort[$sortBy], SORT_ASC, $appMarkets);
}
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">

<html>
<head>
	<title></title>
	<link rel="stylesheet" type="text/css" href="css/buymanual.css">
</head>
<body>
<div class="content">
<div class="message"><?PHP echo $message; ?></div>
<div class="menu"><a href="index.php">Return</a></div>
<div class="menu">
<form action="" method="post">
Sort by 
<select name="sortBy">
<option value="baseVolume" <?PHP if ($sortBy == "baseVolume") {echo "selected";} ?>>Base volume</option>
<option value="priceShift" <?PHP if ($sortBy == "priceShift") {echo "selected";} ?>>Price change 24h</option>
<option value="percent_change_7d" <?PHP if ($sortBy == "percent_change_7d") {echo "selected";} ?>>Price change 7d</option>
</select>
<input type="radio" name="sortOrder" value="desc" <?PHP if ($sortOrder == "desc") {echo "checked";} ?>> desc
<input type="radio" name="sortOrder" value="asc" <?PHP if ($sortOrder == "asc") {echo "checked";} ?>> asc
<input type = "submit" name = "submit" value = "Ok">
</form>
<form action="" method="post">
<input type = "submit" name = "submit" value = "Buy selected">
<input type="radio" name="buyMode" value="auto"> auto
<input type="radio" name="buyMode" value="program" checked> program
</div>

<?PHP
foreach ($appMarkets as $coin) {
    $coinSymbol = substr($coin["marketName"], 4);
	$coinFileName = strtolower($coinSymbol);
	$coinInfo = "24h change: " . $coin["priceShift"] . "<BR>" . 
		"BV: " . $coin["baseVolume"];
	//Output coin blocks
	echo "<div class=\"market\">";
	echo "<BR><span class=\"header\">";
	echo "<a href=https://bittrex.com/Market/Index?MarketName=" . $coin["marketName"] . " target=_blank>";
	echo '<img src="images/' . $coinFileName . '.png" width="80"></a>';
	echo '<input type="checkbox" name="buyManual[]" value="' . $coin["marketName"] . ';;' . $coinInfo . '">&nbsp;';
	echo "<a href=https://bittrex.com/Market/Index?MarketName=" . $coin["marketName"] . " target=_blank>";
	echo $coin["name"] . " (<span style=\"color: Black;\">" . $coinSymbol . "</span>)</a></span>";
	echo "<BR>";
	
	if (isset($coin["chartCode"])) {echo $coin["chartCode"];}
    
	echo '$' . round(($coin['price'] * $bitcoinPrice), 2) . ' ';
	echo rtrim(number_format($coin['price'],10), '0');
	echo ' ';
	if ($coin["priceShift"] < 0) {$color = "Red";} else {$color = "Green";}
	echo "<span style=\"color:" . $color . "\">" . $coin["priceShift"] . "%</span><br>";
	if ($coin["percent_change_7d"] < 0) {$color = "Red";} else {$color = "Green";}
	echo "7d change: <span style=\"color:" . $color . "\">" . $coin["percent_change_7d"] . "%</span><BR>";
	echo "Base volume: " . round($coin["baseVolume"], 0) . " BTC<br>";
	echo '<a href="https://coinmarketcap.com/currencies/' . $coin["cmcId"] . '/" target="_blank" style="color: SteelBlue;">Coinmarketcap</a>';
	
	echo "</div>";
}


function buyCoins($readyToBuy, $amount)
{
	//Buy coins through API call and put the record in database
	foreach ($readyToBuy as $pdata)
	{
	    $pdata_arr = explode(";;", $pdata);
		$coin = $pdata_arr[0];
		$coinInfo = $pdata_arr[1];
		Bittrex::buyImmediately($coin, $amount);
	}
}


function buyProgram($readyToBuy)
{
    global $db;
	//Just put the selected coins in "program" table at this time
	foreach ($readyToBuy as $pdata)
	{
	    $pdata_arr = explode(";;", $pdata);
		$coin = $pdata_arr[0];
		//Put the record in database
		$statement = $db->prepare('INSERT INTO program (coin) VALUES (?)');
	    $statement->bind_param('s', $coin);
		$statement->execute();
		$statement->close();
	}
}

$endTime = microtime(true);
$execTime = $endTime - $startTime;

echo '<div style="clear: both;margin-left: 10px;">Script execution time: ' . round($execTime, 2) . ' sec</div><BR>';
?>

</form></div></body></html>
