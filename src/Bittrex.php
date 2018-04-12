<?PHP
class Bittrex {
    /**
	* Bittrex returned data format:
	* 
	* success	true
	* message	""
	* result
	*    0	
	* 		MarketName	"BTC-2GIVE"
	* 		High	9.9e-7
	* 		Low	9.5e-7
	* 		Volume	1058747.80887589
	* 		Last	9.8e-7
	* 		BaseVolume	1.01310428
	* 		TimeStamp	"2018-03-15T21:32:22.647"
	*    	Bid	9.7e-7
	* 		Ask	9.8e-7
	* 		OpenBuyOrders	141
	* 		OpenSellOrders	1048
	* 		PrevDay	9.6e-7
	* 		Created	"2016-05-16T06:44:15.287"
    */
	
	
	/**
	* Function refreshDatabase updates bittrex database, 
	* downloading all the coins from there to local database
	* 
	* Sample usage:
	* $period = time() - 86400; Update if more than 24 hours passed since last update
	* Bittrex::refreshDatabase($period);
	* 
	* @var timestamp INT
	* If this number is higher than lowest last updated timestamp then update happens
	*/
    public static function refreshDatabase($timestamp) {
	    global $db;
		//Create table if it doesn't exist
		$query = $db->query('SHOW TABLES LIKE "bittrex"');
		$num_rows = $query->num_rows;
        if ($num_rows == 0) {
            $query = $db->query("CREATE TABLE bittrex (
			MarketName varchar(50),
			High varchar(50),
			Low varchar(50),
			Volume varchar(50),
			Last varchar(50),
			BaseVolume varchar(50),
			Bid varchar(50),
			Ask varchar(50),
			OpenBuyOrders varchar(50),
			OpenSellOrders varchar(50),
			PrevDay varchar(50),
			Created varchar(50),
			timestamp varchar(50)
            )");
		}
		//Check if we need to update
		$query = $db->query('SELECT * FROM bittrex LIMIT 1');
		while ($row = $query->fetch_assoc()) {
			if ($row['timestamp'] >= $timestamp) {
		        return true;
			}
		}
		//Get full ticker array and write to db
		$curl = new Curl;
		$curl->link = 'https://bittrex.com/api/v1.1/public/getmarketsummaries';
		$request = $curl->curlRequest();
		if ($request) {
			$array = json_decode($request, true);
		} else {
			return false;
		}
		//Delete old table
		$db->query("DELETE FROM bittrex");
		//Disable autocommit for this transaction
		$db->autocommit(false);
		//Write new data
		foreach ($array['result'] as $coin) {
		$db->query("INSERT INTO bittrex (
			MarketName,
			High,
			Low,
			Volume,
			Last,
			BaseVolume,
			Bid,
			Ask,
			OpenBuyOrders,
			OpenSellOrders,
			PrevDay,
			Created,
			timestamp
			) VALUES (
			'" . $coin['MarketName'] . "', '" .
			$coin['High'] . "', '" .
			$coin['Low'] . "', '" .
			$coin['Volume'] . "', '" .
			$coin['Last'] . "', '" .
			$coin['BaseVolume'] . "', '" .
			$coin['Bid'] . "', '" .
			$coin['Ask'] . "', '" .
			$coin['OpenBuyOrders'] . "', '" .
			$coin['OpenSellOrders'] . "', '" .
			$coin['PrevDay'] . "', '" .
			$coin['Created'] . "', '" .
			time() .
			"')");
		}
		//Commit transaction
		$db->commit();
		//Turn autocommit back on
		$db->autocommit(true);
		return true;
    }
	
    /**
	* Function getCoinPrice returns price of the coin or 'false' on failure
	*
	* @var $symbol STRING (btc, eth, nano, etc...)
	*
	* @return STRING 'Bid' database field value or false on error
	*/
	public static function getCoinPrice($symbol) {
        global $db;
	    $src = strtoupper('BTC-' . $symbol);
		//Lookup coin in bittrex database
		$query = $db->query('SELECT * FROM bittrex WHERE MarketName="' . $src . '"');
		while ($row = $query->fetch_assoc()) {
		    return $row['Bid'];
		}
	    return false;
	}
	
    /**
	* Function getBalance gets current available Bittrex account balance in BTC
	* and stores it in 'settings' database
	*/
	public static function getBalance() {
		global $db;
		$curl = new Curl;
		$curl->link = 'https://bittrex.com/api/v1.1/account/getbalance?currency=BTC';
		$curl->target = 'bittrex';
		$request = $curl->curlRequest();
		if ($request) {
			$obj = json_decode($request, true);
		} else {
			return false;
		}
		$setting = 'bittrexBalance';
		$value = $obj['result']['Available'];
		$statement = $db->prepare('UPDATE settings SET Value = ? WHERE Setting = ?');
		$statement->bind_param('ss', $value, $setting);
		$statement->execute();
		$statement->close();
    }
	
    /**
	* Function isOpen checks Bittrex order uuid and returns its current state
	*
	* @var $id STRING Bittrex order uuid
	*
	* @return BOOL (true if order is open, false if it's closed)
	* Failed request returns true (as if the order was open)
	*/
	public static function isOpen($id) {
		$success = false;
		$orderOpen = true;
	    //Call Bittrex API
		$curl = new Curl;
		$curl->link = 'https://bittrex.com/api/v1.1/account/getorder?uuid=' . $id;
		$curl->target = 'bittrex';
		$request = $curl->curlRequest();
		if ($request) {
			$obj = json_decode($request, true);
		} else {
			return true;
		}
		$success = $obj["success"];
		$message = $obj["message"];
		if ($success == true) {
	        $orderOpen = $obj["result"]["IsOpen"];
		}
	    return $orderOpen;
	}
	
	/**
	*
	* Function switchToAuto transfers coin record from 'program' to 'coins' database
	*
	* @var STRING $id, coin id in 'program' database
	*
	*/
	public static function switchToAuto($id) {
	    global $db;
	    //Get coin data
	    $results = $db->query('SELECT * FROM program WHERE id = "' . $id . '"');
	    $coin = $results->fetch_assoc();
		$comment = $coin['comment'] . '<br>Switched to auto';
		//Put the coin in "coins" table
		$statement = $db->prepare('INSERT INTO coins (marketName, buyDate, buyPrice, buyAmount, uuid, comment) VALUES (?, ?, ?, ?, ?, ?)');
	    $statement->bind_param('siddss', $coin["coin"], $coin["buyDate"], $coin["buyPrice"], $coin["buyAmount"], $coin["buyId"], $comment);
		$statement->execute();
		$statement->close();
		//Delete coin from "program" table
	    $db->query('DELETE FROM program WHERE id="' . $id . '"');
	}
    
	/**
	*
	* Function placeBuyOrder places buy order with Bittrex
	* and writes info into 'program' database
	*
	* @var STRING $coin, format 'BTC-ETH' Market type for Bittrex order requests
	*
	* @var FLOAT $buyPrice, buying price in BTC for the selected market
	*
	* @var FLOAT $amount, amount of trade in BTC for Bittrex order requests
	*
	* @var STRING $id, coin id in 'program' database
	*
	* @return STRING, Bittrex response message
	*
	*/
	function placeBuyOrder ($coin, $buyPrice, $amount, $id) {
	    global $db, $mode;
		$buyAmount = $amount / $buyPrice;
		$success = true;
		$message = "Placed buy order (simulated)";
		$uuid = "simulation_" . time();
		
	    //If _mode_ is live, call Bittrex API
		if ($mode == "live")
		{
			$curl = new Curl;
			$curl->link = 'https://bittrex.com/api/v1.1/market/buylimit?market='.$coin.'&quantity='.$buyAmount.'&rate='.$buyPrice;
			$curl->target = 'bittrex';
			$request = $curl->curlRequest();
			if ($request) {
				$obj = json_decode($request, true);
			} else {
				return 'Curl error, no action taken';
			}
			$success = $obj["success"];
			$message = $obj["message"];
			$uuid = $obj["result"]["uuid"];
	    }
		if ($success == true)
		{
			$comment = "Buy order placed: " . $mode . ", program @ " . $buyPrice . " (spent " . $amount . " BTC)<BR>";
			//Put the record in database
			$statement = $db->prepare("UPDATE program SET buyPrice = ?, buyAmount = ?, buyId = ?, comment = ? WHERE id = ?");
		    $statement->bind_param("ddsss", $buyPrice, $amount, $uuid, $comment, $id);
		    $statement->execute();
			$statement->close();
		}
		return $message;
    }
    
	/**
	*
	* Function placeSellOrder places sell order with Bittrex
	* and writes info into 'program' database
	*
	* @var STRING $coin, format 'BTC-ETH' Market type for Bittrex order requests
	*
	* @var FLOAT $sellPrice, sell price in BTC for the selected market
	*
	* @var FLOAT $amount, amount of trade in BTC for Bittrex order requests
	*
	* @var STRING $id, coin id in 'program' database
	*
	* @return STRING, Bittrex response message
	*
	*/
	public static function placeSellOrder ($coin, $sellPrice, $amount, $id) {
	    global $db, $mode;
		$success = true;
		$uuid = "simulation_" . time();
		$message = "Placed sell order (simulated)";
		
	    //If _mode_ is live, call Bittrex API
		if ($mode == "live") {
			$curl = new Curl;
			$curl->link = 'https://bittrex.com/api/v1.1/market/selllimit?market=' . $coin . '&quantity=' . $amount . '&rate=' . $sellPrice;
			$curl->target = 'bittrex';
			$request = $curl->curlRequest();
			if ($request) {
				$obj = json_decode($request, true);
			} else {
				return 'Curl error, no action taken';
			}
			$success = $obj["success"];
			$message = $obj["message"];
			$uuid = $obj["result"]["uuid"];
	    }
		if ($success == true) {
			$comment = "Sell order placed: " . $mode . ", program @ " . $sellPrice . " (" . $amount . " coins)<br>";
			//Put the record in database
			$statement = $db->prepare("UPDATE program SET sellPrice = ?, sellId = ?, comment = ? WHERE id = ?");
		    $statement->bind_param("dssi", $sellPrice, $uuid, $comment, $id);
		    $statement->execute();
			$statement->close();
		}
		return $message;
	}
    
	/**
	*
	* Function buyImmediately places buy order that closes immediately
	* and writes info into 'program' database
	*
	* @var STRING $coin, format 'BTC-ETH' Market type for Bittrex order requests
	*
	* @var FLOAT $amount, amount of trade in BTC for Bittrex order requests
	*
	* @var STRING $id, coin id in 'program' database
	*
	* @return STRING, Bittrex response message
	*
	*/
	public static function buyImmediately($coin, $amount, $id) {
	    global $db, $mode;
		$success = true;
	    $message = "Bought immediately (simulated)";
	    
		$curl = new Curl;
		$curl->link = 'https://bittrex.com/api/v1.1/public/getorderbook?market=' . $coin .  '&type=sell';
		$request = $curl->curlRequest();
		if ($request) {
	        $obj = json_decode($request, true);
		} else {
		    return 'Curl error, no action taken';
		}
		$orderBook = $obj['result'];
		$neededQuantity = 0;
		
		foreach ($orderBook as $orderId => $orderData) {
			$neededQuantity += $orderData["Quantity"];
			if ($neededQuantity > ($amount / $orderData["Rate"])) {
			    $buyDate = time();
				$buyPrice = $orderData["Rate"];
				$orderQuantity = $amount / $buyPrice;
				$uuid = "simulation_" . $buyDate;
				
			    //If _mode_ is live, call Bittrex API
				if ($mode == "live") {
					$curl->link = 'https://bittrex.com/api/v1.1/market/buylimit?market=' . $coin . '&quantity=' . $orderQuantity . '&rate=' . $buyPrice;
					$curl->target = 'bittrex';
					$request = $curl->curlRequest();
					if ($request) {
				        $obj = json_decode($request, true);
					} else {
					    return 'Curl error, no action taken';
					}
					$success = $obj["success"];
		            $message = $obj["message"];
					$uuid = $obj["result"]["uuid"];
			    }
				if ($success == true) {
					//Put the record in database
					$comment = "Buy: " . $mode . ", immediately @ " . $buyPrice . " (spent " . $amount . " BTC)<br>";
					$statement = $db->prepare("UPDATE program SET buyDate = ?, buyPrice = ?, buyAmount = ?, buyId = ?, comment = ? WHERE id = ?");
				    $statement->bind_param("iddssi", $buyDate, $buyPrice, $amount, $uuid, $comment, $id);
					$statement->execute();
					$statement->close();
				}
				break;
			}
		}
	return $message;
	}
	
    /**
	*
	* Function sellImmediately places sell order that closes immediately
	* and writes info into 'program' database
	*
	* @var STRING $coin, format 'BTC-ETH' Market type for Bittrex order requests
	*
	* @var FLOAT $sellAmount, amount of trade in BTC for Bittrex order requests
	*
	* @var STRING $id, coin id in 'program' database
	*
	* @return STRING, Bittrex response message
	*
	*/
	public static function sellImmediately($coin, $sellAmount, $id) {
	    global $db, $mode;
		$success = true;
		$message = "Sold immediately (simulated)";
		
		$curl = new Curl;
		$curl->link = 'https://bittrex.com/api/v1.1/public/getorderbook?market=' . $coin .  '&type=buy';
		$request = $curl->curlRequest();
		if ($request) {
	        $obj = json_decode($request, true);
		} else {
		    return 'Curl error, no action taken';
		}
		$orderBook = $obj['result'];
	    
		$neededQuantity = 0;
		foreach ($orderBook as $orderId => $orderData)
		{
		    $neededQuantity += $orderData["Quantity"];
			if ($neededQuantity > $sellAmount)
			{
			    $sellDate = time();
				$sellPrice = $orderData["Rate"];
		        $uuid = "simulation_" . $sellDate;
				
			    //If _mode_ is live, call Bittrex API
				if ($mode == "live") {
					$curl = new Curl;
					$curl->link = 'https://bittrex.com/api/v1.1/market/selllimit?market='.$coin.'&quantity='.$sellAmount.'&rate='.$sellPrice;
					$curl->target = 'bittrex';
					$request = $curl->curlRequest();
					if ($request) {
				        $obj = json_decode($request, true);
					} else {
					    return 'Curl error, no action taken';
					}
					$success = $obj["success"];
			        $message = $obj["message"];
					$uuid = $obj["result"]["uuid"];
			    }
				//Update database
				if ($success == true)
				{
					$comment = "Sell: " . $mode . ", immediately @ " . $sellPrice . "<br>";
					//Put the record in database
					$statement = $db->prepare("UPDATE program SET sellDate = ?, sellPrice = ?, sellId = ?, comment = ? WHERE id = ?");
				    $statement->bind_param("idssi", $sellDate, $sellPrice, $uuid, $comment, $id);
				    $statement->execute();
					$statement->close();
				}
				break;
			}
		}
	    return $message;
	}
	
    /**
	* Function cancelBuyOrder cancels Bittrex order
    * and writes changed data into 'program' database
	*
	* @var $id STRING Bittrex order uuid
	*
	* @return STRING, status message
	*/
	public static function cancelBuyOrder($uuid)
	{
	    global $db, $mode;
		$success = true;
		$message = "Buy order canceled (simulated)";
	    //If _mode_ is live, call Bittrex API
		if ($mode == "live") {
			$curl = new Curl;
		    $curl->link = 'https://bittrex.com/api/v1.1/market/cancel?uuid='.$uuid;
			$curl->target = 'bittrex';
			$request = $curl->curlRequest();
			if ($request) {
				$obj = json_decode($request, true);
			} else {
				return 'Curl error, no action taken';
			}
			$success = $obj["success"];
			$message = $obj["message"];
	    }
		if ($success == true) {
			$comment = "Buy order canceled: " . $mode . ", program<br>";
			//Put the record in database
			$newId = NULL;
			$statement = $db->prepare("UPDATE program SET buyId = ?, comment = ? WHERE buyId = ?");
		    $statement->bind_param("sss", $newId, $comment, $uuid);
		    $statement->execute();
			$statement->close();
	    }
		return $message;
	}
	/**
	* Function cancelSellOrder cancels Bittrex order
	* and writes changed data into 'program' database
	*
	* @var $id STRING Bittrex order uuid
	*
	* @return STRING, status message
	*/
	public static function cancelSellOrder($uuid)
	{
	    global $db, $mode;
		$success = true;
		$message = "Sell order canceled (simulated)";
	    //If _mode_ is live, call Bittrex API
		if ($mode == "live") {
			$curl = new Curl;
		    $curl->link = 'https://bittrex.com/api/v1.1/market/cancel?uuid='.$uuid;
			$curl->target = 'bittrex';
	        $request = $curl->curlRequest();
			if ($request) {
			    $obj = json_decode($request, true);
			} else {
			    return 'Curl error, no action taken';
			}
			$success = $obj["success"];
			$message = $obj["message"];
	    }
		if ($success == true) {
			$comment = "Sell order canceled: " . $mode . ", program<BR>";
			//Put the record in database
			$newId = NULL;
			$statement = $db->prepare("UPDATE program SET sellId = ?, comment = ? WHERE sellId = ?");
		    $statement->bind_param("sss", $newId, $comment, $uuid);
		    $statement->execute();
			$statement->close();
	    }
		return $message;
	}
}
?>
