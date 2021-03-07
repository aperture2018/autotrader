<?php
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
$sellMode = $coinData['sellMode'];

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

// Selling mode switch
if (isset($_POST['sellMode'])) {
    if ($_POST['sellMode'] == 'auto' || $_POST['sellMode'] == 'program') {
	    $sellMode = $_POST['sellMode'];
        $db->query('UPDATE program SET sellMode = "' . $sellMode . '" WHERE id = "' . $id . '"');
	}
	// If selling mode is auto
	if ($sellMode == 'auto') {
	    // Cancel buy order, if it is set but not yet filled
		if ($coinData['buyId'] != '' && $coinData['buyDate'] == '') {
		    $message .= "<br>";
		    $message .= Bittrex::cancelBuyOrder($coinData['buyId']);
		}
	    // Buy immediately if not yet bought
	    if ($coinData['buyId'] == '') {
		    // Determine the amount to spend
			if (isset($_POST['buyAmount'])) {
			    $am = $_POST['buyAmount'];
			} else {
			    $am = $amount;
			}
			$message .= Bittrex::buyImmediately($coinData["coin"], $am, $id);
		}
		// Cancel sell order, if it is set
		if ($coinData['sellId'] != '') {
		    $message .= "<br>";
		    $message .= Bittrex::cancelSellOrder($coinData['sellId']);
		}
	}
}

// Other POST calls

// Buy form call
if (isset($_POST['buy_form'])) {
	// If coin buy order is not yet placed
	if ($coinData['buyId'] == '') {
	    // Update buy price in database
	    $db->query('UPDATE program SET buyPrice = "' . $_POST["buyPrice"] . '" WHERE id = "' . $id . '"');
		// Get buy amount from database, or set default value from settings
		$buyAmount = $settings['amount'];
		if ($coinData['buyAmount'] > 0) {
		    $buyAmount = $coinData['buyAmount'];
		}
		if ($_POST['buyPrice'] != '' && $_POST['buyPrice'] != 0) {
			$message = Bittrex::placeBuyOrder($coinData['coin'], $_POST['buyPrice'], $buyAmount, $id);
		}
	}
}

// Buy amount call
if (isset($_POST['buy_amount'])) {
	// If coin buy order is not yet placed
	if ($coinData['buyId'] == '') {
		$db->query('UPDATE program SET buyAmount = "' . $_POST["buyAmount"] . '" WHERE id = "' . $id . '"');
	}
}

// Buy immediately
if (isset($_POST['buy_now'])) {
	// If coin buy order is not yet placed
	if ($coinData['buyId'] == '') {
	    // Get buy amount from database, or set default value from settings
		$buyAmount = $settings['amount'];
		if ($coinData['buyAmount'] > 0) {
		    $buyAmount = $coinData['buyAmount'];
		}
	    $message = Bittrex::buyImmediately($coinData['coin'], $buyAmount, $id);
	}
}

// Sell form call
if (isset($_POST['sell_form'])) {
    // If coin sell order is not yet placed
	if ($coinData['sellId'] == '') {
        $db->query('UPDATE program SET sellPrice = "' . $_POST["sellPrice"] . '" WHERE id = "' . $id . '"');
		// If coin _buyDate_ is set
		if ($coinData['buyDate'] != '') {
		    $sellAmount = $coinData['buyAmount'] / $coinData['buyPrice'];
		    if ($_POST['sellPrice'] != '' && $_POST['sellPrice'] != 0) {
	            $message = Bittrex::placeSellOrder($coinData['coin'], $_POST['sellPrice'], $sellAmount, $id);
			}
		}
	}
}

// Sell immediately
if (isset($_POST['sell_now'])) {
	// If coin sell order is not yet placed
	if ($coinData['sellId'] == '' && $coinData['buyDate'] != '') {
	    $sellAmount = $coinData['buyAmount'] / $coinData['buyPrice'];
	    $message = Bittrex::sellImmediately($coinData['coin'], $sellAmount, $id);
		header('Location: index.php');
		exit;
	}
}

// Update stop loss field
if (isset($_POST['stoploss_form'])) {
    // We can always update _stopLoss_ field
    $db->query('UPDATE program SET stopLoss = "' . $_POST["stopLoss"] . '" WHERE id = "' . $id . '"');
}

//

//

//

//

//

//

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
		$coinAmount = round(($coinData["buyAmount"] / $coinData["buyPrice"]), 2);
	    $currentInfo = 'Bought ' . $coinAmount . ' ' . $coinSymbol . ' at ' . rtrim($coinData["buyPrice"], '0');
	} else {
		$currentInfo .= '<form action="" method="post">Buy order placed at ' .
		rtrim(number_format($coinData["buyPrice"],10), '0') .
		'<input type = "hidden" name = "id" value = "' . $id . '">
		<input type = "hidden" name = "uuid" value = "' . $coinData["buyId"] . '">
		<input type = "hidden" name = "action" value = "cancelBuyOrder">
		<input type = "submit" name = "submit" value = "Cancel"></form>
		';
	}
}

// Check if sell fields should be greyed out
// Show sell order info
$sellPriceDisabled = "";
if ($coinData["sellId"] != "") {
    $sellPriceDisabled = 'disabled="disabled"';
	$currentInfo .= '<form action="" method="post">Sell order placed at ' .
	rtrim(number_format($coinData["sellPrice"],10), '0') .
	'<input type = "hidden" name = "id" value = "' . $id . '">
	<input type = "hidden" name = "uuid" value = "' . $coinData["sellId"] . '">
	<input type = "hidden" name = "action" value = "cancelSellOrder">
	<input type = "submit" name = "submit" value = "Cancel"></form>
	';
}

// Display message about auto mode active
if ($sellMode == 'auto') {
    $currentInfo .= '<br>Selling in auto mode';
    $sellPriceDisabled = 'disabled="disabled"';
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

<div class="main_form">
	<div class="header"><a href="https://bittrex.com/Market/Index?MarketName=<?PHP echo $coinData["coin"]; ?>" target="_blank">
	<img src="images/<?PHP echo strtolower($coinSymbol); ?>.png" align="middle" width="80"></a>&nbsp;<a href="https://bittrex.com/Market/Index?MarketName=<?PHP echo $coinData["coin"]; ?>" target="_blank"><?PHP echo $coinName; ?> (<?PHP echo $coinSymbol; ?>)</a>
	<?PHP if ($deleteCoin) {echo '<span class="deleteButton">[<a href="program.php?action=delete&id=' . $id . '">x</a>]</span>';} ?>
	</div>
	
	<?php
	if ($message != "") {echo '<div class="message">' . $message . '</div>';}
	if ($currentInfo != "") {echo '<div class="currentInfo">' . $currentInfo . '</div>';}
	?>
	
	
	<form action="" method="post">
		<div class="form_text">Buy at:</div><input type = "text" name = "buyPrice" id="buyPrice" size="12" value="<?PHP {echo preg_replace('/\.$/', '.0', rtrim($buyPrice, '0'));} ?>" <?PHP echo $buyPriceDisabled; ?>> btc&nbsp;
		<input type = "hidden" name = "id" value = "<?PHP echo $id; ?>">
	    <input type = "hidden" name = "buy_form" value = "1">
	    <input type = "submit" name = "submit" value = "Place order" <?PHP echo $buyPriceDisabled; ?>>
	</form>
	
	<form action="" method="post">
	    <div class="form_text">Spend:</div><input type = "text" name = "buyAmount" id="buyAmount" size="12" value="<?PHP {echo preg_replace('/\.$/', '.0', rtrim($amount, '0'));} ?>" <?PHP echo $buyAmountDisabled; ?>> btc&nbsp;
		<input type = "hidden" name = "id" value = "<?PHP echo $id; ?>">
	    <input type = "hidden" name = "buy_amount" value = "1">
	    <input type = "submit" name = "submit" value = "Set" <?PHP echo $buyPriceDisabled; ?>>
	</form>
		
	<form action="" method="post">
	    <div class="form_text">Sell at:</div><input type = "text" name = "sellPrice" id="sellPrice" size="12" value="<?PHP {echo preg_replace('/\.$/', '.0', rtrim($sellPrice, '0'));} ?>" <?PHP echo $sellPriceDisabled; ?>> btc
		<input type = "text" name = "sellPricePrc" id="sellPricePrc" size="4" value="" <?PHP echo $sellPriceDisabled; ?>> %&nbsp;
		<input type = "hidden" name = "id" value = "<?PHP echo $id; ?>">
	    <input type = "hidden" name = "sell_form" value = "1">
		<input type = "submit" name = "submit" value = "Set" <?PHP echo $sellPriceDisabled; ?>>
	</form>
	
	<form action="" method="post">
		<div class="form_text">Stop loss:</div>
		<input type = "text" name = "stopLoss" id="stopLoss" size="12" value="<?PHP {echo preg_replace('/\.$/', '.0', rtrim($stopLoss, '0'));} ?>"> btc
		<input type = "text" name = "stopLossPrc" id="stopLossPrc" size="4" value=""> %&nbsp;
		<input type = "hidden" name = "id" value = "<?PHP echo $id; ?>">
	    <input type = "hidden" name = "stoploss_form" value = "1">
	    <input type = "submit" name = "submit" value = "Set">
	</form>
	
<div style="display: inline-block; margin-top: 5px;">
	<form action="" method="post">
		<input type = "hidden" name = "id" value = "<?PHP echo $id; ?>">
	    <input type = "hidden" name = "buy_now" value = "1">
	    <input type = "submit" name = "submit" value = "Quick buy" <?PHP echo $buyPriceDisabled; ?>>
	</form>
</div>
	
<div style="display: inline-block;">
	<form action="" method="post">
		<input type = "hidden" name = "id" value = "<?PHP echo $id; ?>">
	    <input type = "hidden" name = "sell_now" value = "1">
	    <input type = "submit" name = "submit" value = "Quick sell" <?PHP echo $sellPriceDisabled; ?>>
	</form>
</div>
<br>
<div style="display: inline-block; margin-top: 5px;">
	<form action="" method="post">
		<input type="hidden" name="sellMode" value="<?PHP if ($sellMode == 'program') {$swMode = 'auto';} else {$swMode = 'program';} echo $swMode; ?>">
		<input type = "hidden" name = "id" value = "<?PHP echo $id; ?>">
		<input type = "submit" name = "submit" value = "Switch to <?php echo $swMode; ?>">
	</form>
</div>
<div style="display: inline-block;">
	<form action="index.php">
		<input type = "submit" value = "Back">
	</form>
</div>

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
</div>

</div>
</body>
</html>