<?PHP
class ProgramMonitor {
    public static function update() {
	    global $db;
		// Get coins from database
		$coins = array();
		$results = $db->query('SELECT * FROM program WHERE sellDate IS NULL');
		while ($row = $results->fetch_assoc()) {
		    $coins[] = $row;
		}
		// Scan the array
		foreach ($coins as $coin)
		{
		    $timeStamp = time();
			// If "buyId" is set and "buyDate" is empty, check order
			if ($coin['buyId'] != '' && $coin['buyDate'] == '') {
			    // If buy order was fulfilled, update the database
			    if (!Bittrex::isOpen($coin['buyId'])) {
				    $db->query('UPDATE program SET buyDate = "' . $timeStamp . '" WHERE id = "' . $coin["id"] . '"');
					// If sell price is set, place sell order
					if ($coin['sellPrice'] != '' && $coin['sellPrice'] != 0) {
						$sellAmount = $coin['buyAmount'] / $coin['buyPrice'];
					    Bittrex::placeSellOrder($coin['coin'], $coin['sellPrice'], $sellAmount, $coin['id']);
					}
				}
			}
		    // If sell order was placed, check it
		    if ($coin['sellId'] != '') {
			    // If sell order was fulfilled, update the database
			    if (!Bittrex::isOpen($coin['sellId'])) {
				    $db->query('UPDATE program SET sellDate = "' . $timeStamp . '" WHERE id = "' . $coin["id"] . '"');
				}
			}
			// Stop loss check
			if ($coin['stopLoss'] > 0) {
				// Get current coin price
				$query = $db->query('SELECT Bid FROM bittrex WHERE MarketName = "' . $coin['coin'] . '"');
			    while ($result = $query->fetch_assoc()) {
			        $currentPrice = $result['Bid'];
				}
				// If coin is bought and price below stop loss value
				if ($coin['buyDate'] > 0 && $coin['stopLoss'] > $currentPrice) {
				    // Check if sell order is set and cancel it, then sell coin immediately
					if ($coin['sellId'] != '') {
					    Bittrex::cancelSellOrder($coin['sellId']);
					}
					$sellAmount = $coin['buyAmount'] / $coin['buyPrice'];
					Bittrex::sellImmediately($coin['coin'], $sellAmount, $coin['id']);
				}
			}
		}
	}
	
	public static function autoSell() {
		global $db;
		
		// Settings
		$results = $db->query('SELECT * FROM settings');
		while ($row = $results->fetch_assoc()) {
		    $settings[$row["Setting"]] = $row["Value"];
		}
		$dropDeviation = $settings["dropDeviation"];
		$minProfit = $settings["minProfit"];
		$reactionTime = $settings["reactionTime"];
		
		// Get coins from database
		$coins = array();
		$results = $db->query('SELECT * FROM program WHERE sellDate IS NULL AND sellMode = "auto"');
		while ($row = $results->fetch_assoc()) {
		    $coins[] = $row;
		}
		// Scan the array
		foreach ($coins as $coin)
		{
			//Get latest bid from monitor table "market_summaries"
			$statement = $db->prepare('SELECT Bid FROM bittrex WHERE MarketName = ?');
			$statement->bind_param("s", $coin["coin"]);
			$statement->bind_result($bid);
		    $statement->execute();
			$statement->fetch();
			$statement->close();
			
			//TEST MODE
			if (!isset($bid)) {continue;}
			
			// PRICE MONITORING LOGIC
			
			if (isset($coin["lastBid"]))
			{
			    $lastBid = $coin["lastBid"];
				$lastPriceAsc = $coin["lastPriceAsc"];
				$lastPriceDesc = $coin["lastPriceDesc"];
				$priceDropTime = $coin["priceDropTime"];
		        
		        if ($bid >= $coin["lastPriceAsc"]) {
				    $lastPriceAsc = $bid;
					$priceDropTime = NULL;
				}
		        if ($bid < $coin["lastPriceAsc"]) {
				    $lastPriceDesc = $bid;
					if (!isset($priceDropTime)) {
					    $priceDropTime = time();
					}
				}
		        if ($bid < $coin["lastPriceDesc"]) {
				    $lastPriceDesc = $bid;
					if (!isset($priceDropTime)) {
					    $priceDropTime = time();
					}
				}
				if ($bid > $coin["lastPriceDesc"] && $bid < $coin["lastPriceAsc"]) {
				    $lastPriceDesc = $bid;
					if (!isset($priceDropTime)) {
					    $priceDropTime = time();
					}
				}
				if ($bid > $coin["lastPriceDesc"] && $bid > $coin["lastPriceAsc"]) {
				    $lastPriceAsc = $bid;
					$lastPriceDesc = $bid;
					$priceDropTime = NULL;
				}
			} else {
			    $lastPriceAsc = $lastPriceDesc = $bid;
			}
			
			// Update database
			$statement = $db->prepare("UPDATE program SET lastPriceAsc = ?, lastPriceDesc = ?, priceDropTime = ?, lastBid = ? WHERE coin = ?");
		    $statement->bind_param("dddds", $lastPriceAsc, $lastPriceDesc, $priceDropTime, $bid, $coin["coin"]);
		    $statement->execute();
			$statement->close();
		    
			// SELLING LOGIC
			$sellAmount = $coin["buyAmount"] / $coin["buyPrice"];
			
			// Sell on stop loss
			if ($coin['stopLoss'] > 0) {
			    $stopLoss = $coin['stopLoss'];
			} else {
			    $stopLoss = ($coin['buyPrice'] - ($coin['buyPrice'] * 0.01 * $settings['stopLoss']));
			}
			if ($bid < $stopLoss) {
			   Bittrex::sellImmediately($coin['coin'], $sellAmount, $coin['id']);
			   // Set buyNow variable
			   $db->query('UPDATE settings SET Value = "1" WHERE Setting = "buyNow"');
			   continue;
			}
			// Sell on price drop
			if ($bid < ($coin["lastPriceAsc"] - ($coin["lastPriceAsc"] * 0.01 * $dropDeviation)) && $bid >= ($coin["buyPrice"] + ($coin["buyPrice"] * 0.01 * $minProfit))) {
			    $currentTime = time();
			    $timeFrame = $currentTime - $priceDropTime;
				if ($timeFrame >= $reactionTime) {
				    Bittrex::sellImmediately($coin['coin'], $sellAmount, $coin['id']);
					// Set buyNow variable
					$db->query('UPDATE settings SET Value = "1" WHERE Setting = "buyNow"');
					continue;
				}
			}
		}
	}
}
?>