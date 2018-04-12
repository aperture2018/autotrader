<?PHP
require 'config.inc.php';
session_start();
$auth = new Auth();
$auth->checkUser();

if (isset($_GET["id"])) {
    $id = $_GET["id"];
} elseif (isset($_POST["id"])) {
    $id = $_POST["id"];
} else {
    exit("No coin selected");
}

//Get settings
$results = $db->query('SELECT * FROM settings');
while ($row = $results->fetch_assoc()) {
    $settings[$row["Setting"]] = $row["Value"];
}
$mode = $settings["mode"];
$amount = $settings["amount"];
$message = $currentInfo = "";

//Get coin data
$results = $db->query('SELECT * FROM program WHERE id = "' . $id . '" AND sellDate IS NULL');
while ($row = $results->fetch_assoc()) {
    $coinData = $row;
}

//Cancel order action
if (isset($_POST["action"])) {
    if ($_POST["action"] == "cancelBuyOrder") {
	    $message = Bittrex::cancelBuyOrder($_POST["uuid"]);
    }
    if ($_POST["action"] == "cancelSellOrder") {
	    $message = Bittrex::cancelSellOrder($_POST["uuid"]);
    }
}

//Can we delete the coin
$deleteCoin = false;
if ($coinData["buyId"] == "") {
    $deleteCoin = true;
	if (isset($_GET["action"]) && $_GET["action"] == "delete") {
	    $db->query('DELETE FROM program WHERE id="' . $id . '"');
		header("Location: index.php");
		exit();
	}
}

//Update database depending on coin status
if (isset($_POST["update"])) {
    //We can always update _stopLoss_ field
    $db->query('UPDATE program SET stopLoss = "' . $_POST["stopLoss"] . '" WHERE id = "' . $id . '"');
	
	//We can update _sellPrice_ only if no sell order is set
	if ($coinData["sellId"] == "") {
        $db->query('UPDATE program SET sellPrice = "' . $_POST["sellPrice"] . '" WHERE id = "' . $id . '"');
		//We can update _buyPrice_ and _buyAmount_ only if no buy order is set
	    if ($coinData["buyId"] == "") {
	        $db->query('UPDATE program SET buyPrice = "' . $_POST["buyPrice"] . '" WHERE id = "' . $id . '"');
			$db->query('UPDATE program SET buyAmount = "' . $_POST["buyAmount"] . '" WHERE id = "' . $id . '"');
		}
	}
	//If coin buy order is not yet placed
	if ($coinData["buyId"] == "" && isset($_POST["buyAmount"]))	{
	    //If "buy now" checkbox is set, buy at current price immediately
		if (isset($_POST["buyNowCheckbox"]) && $_POST["buyNowCheckbox"] == "1") {
		    $message = Bittrex::buyImmediately($coinData["coin"], $_POST["buyAmount"], $id);
		}
		//Otherwise, if _buyPrice_ var is submitted and "buy now" checkbox is not set, place buy order
		elseif (isset($_POST["buyPrice"]) && $_POST["buyPrice"] != "" && $_POST["buyPrice"] != 0) {
			$message = Bittrex::placeBuyOrder($coinData["coin"], $_POST["buyPrice"], $_POST["buyAmount"], $id);
		}
	}
	//If coin _buyDate_ is set, and sell order is not yet placed (sell order canceled manually case)
	if ($coinData["buyDate"] != "" && $coinData["sellId"] == "") {
	    $sellAmount = $coinData["buyAmount"] / $coinData["buyPrice"];
		
		//If "switch to auto" checkbox is set, switch to auto sell mode
		if (isset($_POST["switchAutoCheckbox"]) && $_POST["switchAutoCheckbox"] == "1") {
		    Bittrex::switchToAuto($id);
			header("Location: index.php");
			exit();
		}
		//If "sell now" checkbox is set, sell at current price immediately
		if (isset($_POST["sellNowCheckbox"]) && $_POST["sellNowCheckbox"] == "1") {
		    $message = Bittrex::sellImmediately($coinData["coin"], $sellAmount, $id);
			header("Location: index.php");
			exit();
		}
		//If "sell now" checkbox is not set, and sell price is set, place sell order
		elseif ($coinData["sellPrice"] != "" && $coinData["sellPrice"] != 0) {
	        $message = Bittrex::placeSellOrder ($coinData["coin"], $_POST["sellPrice"], $sellAmount, $id);
		}
	}
}

/**
*
*
*
*
*/

//No API calls past this point, show coin status
//Get coin data
$results = $db->query('SELECT * FROM program WHERE id = "' . $id . '" AND sellDate IS NULL');
while ($row = $results->fetch_assoc()) {
    $coinData = $row;
}
//Buy settings
$buyPrice = $coinData["buyPrice"];
$sellPrice = $coinData["sellPrice"];
$stopLoss = $coinData["stopLoss"];

//Get Bittrex markets data


//Get CMC data
$coinSymbol = substr($coinData['coin'], 4);
$results = $db->query('SELECT * FROM coinmarketcap WHERE symbol = "' . $coinSymbol . '"');
while ($row = $results->fetch_assoc()) {
    $cmcData = $row;
}
$coinName = $cmcData['name'];


//Use common order amount if custom is not set
if ($coinData["buyAmount"] != "") {
    $amount = $coinData["buyAmount"];
}

//Check if buy fields should be greyed out
//Show buy order info
$buyPriceDisabled = $buyAmountDisabled = "";
if ($coinData["buyId"] != "") {
    $buyPriceDisabled = $buyAmountDisabled = 'disabled="disabled"';
	if ($coinData["buyDate"] != "")	{
	    $currentInfo = "Bought at " . $coinData["buyPrice"];
	} else {
		$currentInfo .= '<form action="" method="post">Buy order placed @ ' .
		rtrim(number_format($coinData["buyPrice"],10), '0') .
		'<input type = "hidden" name = "id" value = "' . $id . '">
		<input type = "hidden" name = "uuid" value = "' . $coinData["buyId"] . '">
		<input type = "hidden" name = "action" value = "cancelBuyOrder">
		<input type = "submit" name = "submit" value = "Cancel"></form>
		';
	}
}

//Check if sell fields should be greyed out
//Show sell order info
$sellPriceDisabled = "";
if ($coinData["sellId"] != "") {
    $sellPriceDisabled = 'disabled="disabled"';
	$currentInfo .= '<form action="" method="post">Sell order placed @ ' .
	rtrim(number_format($coinData["sellPrice"],10), '0') .
	'<input type = "hidden" name = "id" value = "' . $id . '">
	<input type = "hidden" name = "uuid" value = "' . $coinData["sellId"] . '">
	<input type = "hidden" name = "action" value = "cancelSellOrder">
	<input type = "submit" name = "submit" value = "Cancel"></form>
	';
}
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">

<html>
<head>
	<title></title>
	<link rel="stylesheet" type="text/css" href="css/program.css">
</head>
<body>

<div class="content">
<div class="header"><a href="https://bittrex.com/Market/Index?MarketName=<?PHP echo $coinData["coin"]; ?>" target="_blank">
<img src="images/<?PHP echo strtolower($coinSymbol); ?>.png" align="middle" width="80"><?PHP echo $coinName; ?> (<?PHP echo $coinSymbol; ?>)</a>
<?PHP if ($deleteCoin) {echo '<span class="deleteButton">[<a href="program.php?action=delete&id=' . $id . '">x</a>]</span>';} ?>
</div>
<div class="menu"><a href="index.php">Back</a></div>

<?PHP 
if ($message != "") {echo '<div class="message">' . $message . '</div>';}
if ($currentInfo != "") {echo '<div class="currentInfo">' . $currentInfo . '</div>';}
?>


<form action="" method="post">
 
  <fieldset>
   <br>
    <legend>Set program</legend>
	Buy at:<br>
	<input type = "text" name = "buyPrice" id="buyPrice" size="12" value="<?PHP {echo preg_replace('/\.$/', '.0', rtrim($buyPrice, '0'));} ?>" <?PHP echo $buyPriceDisabled; ?>> btc
	<input type="checkbox" name="buyNowCheckbox" value="1" <?PHP echo $buyPriceDisabled; ?>> Buy now<br>
    Spend:<br>
	<input type = "text" name = "buyAmount" id="buyAmount" size="12" value="<?PHP {echo preg_replace('/\.$/', '.0', rtrim($amount, '0'));} ?>" <?PHP echo $buyAmountDisabled; ?>> btc<br>
    Sell at:<br>
	<input type = "text" name = "sellPrice" id="sellPrice" size="12" value="<?PHP {echo preg_replace('/\.$/', '.0', rtrim($sellPrice, '0'));} ?>" <?PHP echo $sellPriceDisabled; ?>> btc
	<input type = "text" name = "sellPricePrc" id="sellPricePrc" size="4" value="" <?PHP echo $sellPriceDisabled; ?>> % 
	<input type="checkbox" name="sellNowCheckbox" value="1" <?PHP echo $sellPriceDisabled; ?>> Sell now<br>
	Stop loss:<br>
	<input type = "text" name = "stopLoss" id="stopLoss" size="12" value="<?PHP {echo preg_replace('/\.$/', '.0', rtrim($stopLoss, '0'));} ?>"> btc
	<input type = "text" name = "stopLossPrc" id="stopLossPrc" size="4" value=""> %<br>

	<input type="checkbox" name="switchAutoCheckbox" value="1" <?PHP echo $sellPriceDisabled; ?>> Switch to auto<br>
  </fieldset>

<input type = "hidden" name = "id" value = "<?PHP echo $id; ?>">
<input type = "hidden" name = "update" value = "1">
<input type = "submit" name = "submit" value = "Submit">
</form>

<script>
var buyPrice = document.getElementById('buyPrice'),
    sellPrice = document.getElementById('sellPrice'),
	sellPricePrc = document.getElementById('sellPricePrc'),
	stopLoss = document.getElementById('stopLoss'),
	stopLossPrc = document.getElementById('stopLossPrc'),
	buyAmount = document.getElementById('buyAmount'),
	profit = document.getElementById('profit');
	
	
sellPrice.onchange = function () { // or first.onchange
  sellPricePrc.value = Math.round((parseFloat(sellPrice.value) - parseFloat(buyPrice.value)) / (parseFloat(buyPrice.value) * 0.01));
  var calcProfit = ((parseFloat(buyAmount.value) / parseFloat(buyPrice.value)) * parseFloat(sellPrice.value)) - parseFloat(buyAmount.value);
  calcProfit = calcProfit.toPrecision(4);
  profit.textContent = "Profit: " + calcProfit + " btc";
  
};

sellPricePrc.onchange = function () { // or first.onchange
  sellPrice.value = ((parseFloat(buyPrice.value) * 0.01) * (100 + parseFloat(sellPricePrc.value)));
};

stopLoss.onchange = function () { // or first.onchange
  stopLossPrc.value = Math.round((parseFloat(stopLoss.value) - parseFloat(buyPrice.value)) / (parseFloat(buyPrice.value) * 0.01));
};

stopLossPrc.onchange = function () { // or first.onchange
  stopLoss.value = (parseFloat(buyPrice.value) * 0.01) * (100 + parseFloat(stopLossPrc.value));
};
</script>