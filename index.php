<?PHP
require 'config.inc.php';
session_start();
$auth = new Auth();
$auth->checkUser();

//$period = time();
//echo Bittrex::getBalance();
//echo Bittrex::refreshDatabase($period);
//var_dump(Bittrex::getCoinPrice('zec'));
//$curl = new Curl();
//$curl->link = 'https://cryptocompare.com//media/12318177/ada.png';
//echo $curl->curlRequest();
//phpinfo();
//$test=json_decode(file_get_contents('https://tradesatoshi.com/api/public/getmarketsummaries'), true);
//echo $test;

$results = $db->query('SELECT * FROM settings');
while ($row = $results->fetch_assoc()) {
	$settings[$row["Setting"]] = $row["Value"];
}
//Settings
$mode = "";
$bittrexBalance = $settings['bittrexBalance'];

//Get current Bitcoin price
$qry = $db->query('SELECT Bid FROM bittrex WHERE MarketName = "USD-BTC"');
while ($res = $qry->fetch_assoc()) {
	$bitcoinPrice = $res["Bid"];
}

if ($settings["mode"] == "simulation") {
	$mode = '<span style="color: White;background-color: LightGreen;">&nbsp;simulation mode&nbsp;</span>';
}
if ($settings["mode"] == "stopall") {
	$mode = '<span style="color: White;background-color: Tomato;">&nbsp;service stopped&nbsp;</span>';
}

//Buy settings
if ($settings["buyActive"] != 0) {
	$buyActive = '<span style="color: Green;">on</span>';
} else {
	$buyActive = '<span style="color: Red;">off</span>';
}
if ($settings["sellActive"] != 0) {
	$sellActive = '<span style="color: Green;">on</span>';
} else {
	$sellActive = '<span style="color: Red;">off</span>';
}
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>

<head>
	<title></title>
	<link rel="stylesheet" type="text/css" href="css/index.css">
</head>

<body>
	<div class="content">
		<div class="header"><?PHP echo $mode; ?><span class="balance"><?PHP echo substr($bittrexBalance, 0, 6); ?> btc ($ <?php echo floor($bitcoinPrice * $bittrexBalance); ?>)</span></div>
		<div class="header">
		</div>
		<div class="menu">
			<div class="menuItem"><a href="settings.php">Settings</a></div>
			<div class="menuItem"><a href="buymanual.php" target="_blank">Buy coins</a></div>
			<div class="menuItem">
				<form action="" method="post"><input type="text" name="addCoin" size="4" value=""><input type="submit" name="submit" value="Quick add" class="button"></form>
			</div>
			<div class="menuItem"><a href="stat.php">Stats</a></div>
			<div class="menuItem"><a href="allcoins.php">All coins</a></div>
		</div>

		<?PHP
		//If there is POST request, add coin to program db
		//Accept manual buy request
		if (isset($_POST["addCoin"]) && $_POST["addCoin"] != "") {
			$coin = "BTC-";
			$coin .= strtoupper(trim($_POST["addCoin"]));

			//Check if the coin exists in "bittrex" table
			$check = $db->query('SELECT * FROM bittrex WHERE MarketName = "' . $coin . '"');
			$num_rows = $check->num_rows;
			if ($num_rows > 0) {
				$coin_arr = $check->fetch_assoc();
				//Add coin to "program" table
				$sellMode = 'program';
				$timestamp = time();
				$iniPrice = $coin_arr['Ask'];
				$statement = $db->prepare('INSERT INTO program (coin, timestamp, iniPrice, sellMode) VALUES (?, ?, ?, ?)');
				$statement->bind_param('sids', $coin, $timestamp, $iniPrice, $sellMode);
				$statement->execute();
				$statement->close();
			}
		}

		$queryArray = array();

		//Fetch currently open orders from table "program"
		$results = $db->query("SELECT * FROM program WHERE sellDate IS NULL");
		if ($results->num_rows > 0) {
			echo '<div class="header">Program</div>';
		}
		while ($row = $results->fetch_assoc()) {
			$queryArray[] = $row;
		}
		foreach ($queryArray as $programCoins) {
			//Fetch coin image if needed
			Coinmarketcap::fetchCoinImage(substr($programCoins["coin"], 4));

			// Set basic vars
			$holdingTimeDisplay = "Waiting";
			$buyPrice = $sellPrice = $priceChange = 0;
			$color = "Gray";
			// Fetch current price
			$qry = $db->query('SELECT * FROM bittrex WHERE MarketName = "' . $programCoins["coin"] . '"');
			while ($res = $qry->fetch_assoc()) {
				$currentBid = rtrim($res['Bid'], '0');
				$currentAsk = rtrim($res['Ask'], '0');
			}
			if (isset($programCoins['iniPrice'])) {
				$iniPrice = $programCoins['iniPrice'];
			} else {
				$iniPrice = $currentAsk;
			}
			// Waiting info string (shown on newly added coins)
			$shortPriceChange = round(($currentAsk - $iniPrice) / ($iniPrice / 100), 2);
			if ($shortPriceChange < 0) {
				$color = "Red";
				$symbol = '&xdtri;';
				$shortPriceChange .= '%';
			} elseif ($shortPriceChange > 0) {
				$color = "Green";
				$symbol = '&xutri;';
				$shortPriceChange .= '%';
			} else {
				$color = "Gray";
				$symbol = '';
				$shortPriceChange = '';
			}
			$shortPriceChange = str_replace('-', '', $shortPriceChange);
			//$waitingInfo = 'Prev: ';
			//$waitingInfo .= rtrim(number_format($prevPrice,10), '0');
			//$waitingInfo .= ' <br>Now: ';
			$waitingInfo = rtrim(number_format($currentAsk, 10), '0');
			$waitingInfo .= ' ' . '<span style = "color: ' . $color . ';">' . $symbol . $shortPriceChange . '</span>';

			// If buying order is set
			if ($programCoins["buyId"] != "") {
				$holdingTimeDisplay = "Buying order set";
				//Fetch buy price
				$buyPrice = rtrim($programCoins["buyPrice"], '0');
				$buyInfo = "Buy at:&nbsp;&nbsp;&nbsp;&nbsp;" . rtrim(number_format($buyPrice, 10), '0');
			}
			// If coin is bought
			if ($programCoins["buyDate"] != ""  && $programCoins["buyId"] != "") {
				//Calculate holding duration
				$holdingTimeDisplay = holdingTime($programCoins["buyDate"]);
				//Calculate price change percentage and set color
				$priceChange = round(($currentBid - $buyPrice) / ($buyPrice / 100), 2);
				if ($priceChange < 0) {
					$color = "Red";
					$symbol = '&xdtri;';
				} else {
					$color = "Green";
					$symbol = '&xutri;';
				}
				$buyInfo = "Bought at: " . rtrim(number_format($buyPrice, 10), '0');
			}
			// If sell order is set
			if ($programCoins["sellDate"] == ""  && $programCoins["sellId"] != "") {
				$sellPrice = rtrim($programCoins["sellPrice"], '0');
			}
			$coinName = substr($programCoins["coin"], 4);
			$coinFileName = strtolower($coinName);

			// Coin amount info
			$infoStr = '';
			if ($programCoins['buyDate'] != '') {
				$coinAmount = round(($programCoins['buyAmount'] / $programCoins['buyPrice']), 2);
				$btcAmount = rtrim(number_format($programCoins['buyAmount'], 10), '0');
				$usdAmount = round(($btcAmount * $bitcoinPrice), 0);
				$infoStr = $coinAmount . ' ' . $coinName . ' (' . $btcAmount . ' btc, ' . $usdAmount . ' usd)';
			}

			echo '<div class="market">';
			echo '<a class="tooltip" href="https://bittrex.com/Market/Index?MarketName=' . $programCoins["coin"] . '" target="_blank">';
			echo $coinName . '</a>';

			echo '<BR><a href="program.php?id=' . $programCoins["id"] . '"><img src="images/' . $coinFileName . '.png" width="40" title="' . $infoStr . '"></a>';
			echo '<div style="margin-top: 5px;"></div>';

			if ($programCoins["sellMode"] == "auto") {
				echo '<div class="tradeInfo">Sell at:&nbsp;&nbsp;&nbsp;auto</div>';
			}
			if ($programCoins["sellId"] != "") {
				echo '<div class="tradeInfo">Sell at:&nbsp;&nbsp;&nbsp;' . $sellPrice . '</div>';
			}
			if ($programCoins["buyId"] != "") {
				echo '<div class="tradeInfo">Current:&nbsp;&nbsp;&nbsp;<span class="tradeInfo" style="color:' . $color . '">';
				echo rtrim(number_format($currentBid, 10), '0');
				echo '</span></div>';
				echo '<div class="tradeInfo">' . $buyInfo . '</div>';
			}
			if ($programCoins["buyDate"] != "" && $programCoins["buyId"] != "") {
				echo '<div class = "tradeInfo" style="color:' . $color . '">' . $priceChange . '%</div>';
			}
			if ($programCoins['buyId'] == '') {
				echo '<div class="hold">' . $waitingInfo . '</div>';
			}
			echo '<div class="hold">' . $holdingTimeDisplay . '</div>';
			echo '</div>';
		}
		?>

		<?PHP
		//Fetch closed orders
		$currentTime = time();
		$maxSellDate = $currentTime - 604800;
		$profit = 0;
		$queryArray = array();

		//Fetch results from table "program"
		//$results = $db->query('SELECT * FROM program WHERE sellDate IS NOT NULL AND sellDate > ' . $maxSellDate . ' ORDER BY sellDate ASC');
		$results = $db->query('SELECT * FROM program WHERE sellDate IS NOT NULL ORDER BY sellDate DESC LIMIT 10');
		while ($row = $results->fetch_assoc()) {
			$category = strtoupper(substr($row['sellMode'], 0, 1));
			$queryArray[] = array(
				"category" => $category,
				"coin" => $row["coin"],
				"buyAmount" => $row["buyAmount"],
				"buyPrice" => $row["buyPrice"],
				"buyDate" => $row["buyDate"],
				"sellPrice" => $row["sellPrice"],
				"sellDate" => $row["sellDate"],
				"comment" => $row["comment"],
			);
		}
		//Sort the array by sell date
		uasort($queryArray, function ($a, $b) {
			return $a['sellDate'] - $b['sellDate'];
		});

		if (count($queryArray) > 0) {
			echo '<div class="header">Latest trades</div>';

			foreach ($queryArray as $dbMarket) {
				$currentProfit = (($dbMarket["buyAmount"] / $dbMarket["buyPrice"]) * $dbMarket["sellPrice"]) - ($dbMarket["buyAmount"]);
				$profit += $currentProfit;

				$change = round(($dbMarket["sellPrice"] - $dbMarket["buyPrice"]) / ($dbMarket["buyPrice"] / 100), 2);
				if ($change < 0) {
					$color = "Red";
				} else {
					$color = "Green";
				}

				echo "<div class=\"sold\">";
				echo "<span class=\"sellDate\">" . date('M d H:i', $dbMarket["sellDate"]) . "</span>";
				echo "<span class=\"category\"> " . $dbMarket["category"] . "</span>";
				echo " <a class=tooltip href=https://bittrex.com/Market/Index?MarketName=" . $dbMarket["coin"] . " target=_blank>" . $dbMarket["coin"] . "<span>";
				echo holdingTime($dbMarket["buyDate"]);
				echo "  " . $dbMarket["comment"] .  "</span></a>  <span style=\"color:" . $color . "\">" . $change . "%</span>";
				echo " Bought at: " . rtrim(number_format($dbMarket["buyPrice"], 10), '0');
				//echo  " " . date('m-d H:i', $dbMarket["buyDate"]);
				echo " Sold at: " . rtrim(number_format($dbMarket["sellPrice"], 10), '0');
				echo " Profit: <span style=\"color:" . $color . "\">" . rtrim(number_format($currentProfit, 5), '0') . "</span>";
				echo " (<span style=\"color:" . $color . "\">" . floor($bitcoinPrice * $currentProfit) . " usd</span>)";
				echo "</div>";
			}
			$profitFiat = round(($profit * $bitcoinPrice), 2);;
			echo '<div class="header">Profit: ' . rtrim(number_format($profit, 5), '0') . ' (' . $profitFiat . ' usd)</div>';
		}

		function holdingTime($buyDate)
		{
			$currentTime = time();
			$buyDate = $buyDate;
			$holdingTime = $currentTime - $buyDate;
			$holdingDays = floor($holdingTime / 86400);
			$holdingTime -= ($holdingDays * 86400);
			$holdingHours = floor($holdingTime / 3600);
			$holdingTime -= ($holdingHours * 3600);
			$holdingMin = floor($holdingTime / 60);
			$holdingMin .= "m";
			if ($holdingDays != 0) {
				$holdingDays .= "d ";
			} elseif ($holdingDays == 0) {
				$holdingDays = "";
			}
			if ($holdingHours != 0) {
				$holdingHours .= "h ";
			} elseif ($holdingHours == 0) {
				$holdingHours = "";
			}
			return $holdingDays . $holdingHours . $holdingMin;
		}
		?>

	</div>
	<BR><BR><BR><BR>
</body>

</html>