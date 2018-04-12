<?PHP
//Settings
$stopLoss = $settings["stopLoss"];
$dropDeviation = $settings["dropDeviation"];
$minProfit = $settings["minProfit"];

//Fetch currently open orders
$results = $db->query('SELECT * FROM coins WHERE sellDate IS NULL');
$marketArray = array();
while ($row = $results->fetch_assoc())
{
    $marketArray[] = $row;
}

foreach ($marketArray as $dbMarket)
{
	//Get latest bid from monitor table "market_summaries"
	$statement = $db->prepare('SELECT Bid FROM bittrex WHERE MarketName = ?');
	$statement->bind_param("s", $dbMarket["marketName"]);
	$statement->bind_result($bid);
    $statement->execute();
	$statement->fetch();
	$statement->close();
	
	//TEST MODE
	if (!isset($bid)) { continue; }
	
	$lastPriceAsc = $dbMarket["lastPriceAsc"];
	$lastPriceDesc = $dbMarket["lastPriceDesc"];
    
	//Program logic
	
	//STOP LOSS EVAL
	$sellAmount = $dbMarket["buyAmount"] / $dbMarket["buyPrice"];
	
	if ($bid < ($dbMarket["buyPrice"] - ($dbMarket["buyPrice"] * 0.01 * $stopLoss)))
	{
       sell($dbMarket["marketName"], $sellAmount, "stop loss", $dbMarket["comment"]);
	   //Set buyNow variable
	   $db->query('UPDATE settings SET Value = "1" WHERE Setting = "buyNow"');
	   continue;
	}
	//Sell condition
	if ($bid < ($dbMarket["lastPriceAsc"] - ($dbMarket["lastPriceAsc"] * 0.01 * $dropDeviation)) && $bid >= ($dbMarket["buyPrice"] + ($dbMarket["buyPrice"] * 0.01 * $minProfit)))
	{
	    sell($dbMarket["marketName"], $sellAmount, "drop deviation", $dbMarket["comment"]);
		//Set buyNow variable
		$db->query('UPDATE settings SET Value = "1" WHERE Setting = "buyNow"');
		continue;
	}
    
    //PRICE MONITORING LOGIC
	
	if (isset($dbMarket["lastBid"]))
	{
	    $lastBid = $dbMarket["lastBid"];
		$lastPriceAsc = $dbMarket["lastPriceAsc"];
		$lastPriceDesc = $dbMarket["lastPriceDesc"];
        
        if ($bid >= $dbMarket["lastPriceAsc"]) {$lastPriceAsc = $bid;}
        if ($bid < $dbMarket["lastPriceAsc"]) {$lastPriceDesc = $bid;}
        if ($bid < $dbMarket["lastPriceDesc"]) {$lastPriceDesc = $bid;}
		if ($bid > $dbMarket["lastPriceDesc"] && $bid < $dbMarket["lastPriceAsc"]) {$lastPriceDesc = $bid;}
		if ($bid > $dbMarket["lastPriceDesc"] && $bid > $dbMarket["lastPriceAsc"]) {$lastPriceAsc = $bid; $lastPriceDesc = $bid;}
	}
	else
	{
	    $lastPriceAsc = $lastPriceDesc = $bid;
	}
	
	//Update table "coins"
	$statement = $db->prepare("UPDATE coins SET lastPriceAsc = ?, lastPriceDesc = ?, lastBid = ? WHERE marketName = ?");
    $statement->bind_param("ddds", $lastPriceAsc, $lastPriceDesc, $bid, $dbMarket["marketName"]);
    $statement->execute();
	$statement->close();
}


function sell($coin, $sellAmount, $comment, $coinInfo)
{
    global $db, $mode;
	//Sell coin through API call and put the record in database
	$link = 'https://bittrex.com/api/v1.1/public/getorderbook?market=' . $coin .  '&type=buy';
	$queryResult = curlRequest($link, "");
	$orderBook = $queryResult['result'];
    
	$neededQuantity = 0;
	foreach ($orderBook as $orderId => $orderData)
	{
	    $neededQuantity += $orderData["Quantity"];
		if ($neededQuantity > $sellAmount)
		{
		    $sellDate = time();
			$sellPrice = $orderData["Rate"];
			$uuid = "";
			
		    //If _mode_ is live, call Bittrex API
			if ($mode == "live")
			{
	            $link = 'https://bittrex.com/api/v1.1/market/selllimit?market=' . $coin . '&quantity=' . $sellAmount . '&rate=' . $sellPrice;
	            $obj = curlRequest($link, "bittrex");
				$uuid = $obj["result"]["uuid"];
		    }
			//Update database
			$commentUpd = "Sell: " . $mode . ", " . $comment . "<BR>" . $coinInfo;
			$statement = $db->prepare("UPDATE coins SET sellDate = ?, sellPrice = ?, comment = ?, uuid = ? WHERE marketName = ? AND sellDate IS NULL");
		    $statement->bind_param("idsss", $sellDate, $sellPrice, $commentUpd, $uuid, $coin);
		    $statement->execute();
			$statement->close();
			break;
		}
	}
}
?>