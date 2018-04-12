<?PHP
class Cryptopia {
    /**
	* Cryptopia.co.nz returned data format:
	* {"Success":true,
	* "Message":null,
	* "Data":[{
	*   "TradePairId":1261,
	* 	"Label":"$$$/BTC",
	* 	"AskPrice":0.00000036,
	* 	"BidPrice":0.00000034,
	* 	"Low":0.00000034,
	* 	"High":0.00000037,
	* 	"Volume":51899.66599594,
	* 	"LastPrice":0.00000034,
	* 	"BuyVolume":206312784.04339184,
	* 	"SellVolume":10081992.29139331,
	* 	"Change":0.0, (exluded this one from database)
	* 	"Open":0.00000034,
	* 	"Close":0.00000034,
	* 	"BaseVolume":0.01796679,
	* 	"BuyBaseVolume":5.97514724,
	* 	"SellBaseVolume":154540331.82241016}
	*
	* Function refreshDatabase updates cryptopia database, 
	* downloading all the coins from there to local database
	* 
	* Sample usage:
	* $period = time() - 86400; Update if more than 24 hours passed since last update
	* Cryptopia::refreshDatabase($period);
	* 
	* @var timestamp INT
	* If this number is higher than lowest last updated timestamp then update happens
	*/
    public static function refreshDatabase($timestamp) {
	    global $db;
		//Create table if it doesn't exist
		$query = $db->query('SHOW TABLES LIKE "cryptopia"');
		$num_rows = $query->num_rows;
        if ($num_rows == 0) {
        $query = $db->query("CREATE TABLE cryptopia (
			TradePairId varchar(50),
			Label varchar(50),
			AskPrice varchar(50),
			BidPrice varchar(50),
			Low varchar(50),
			High varchar(50),
			Volume varchar(50),
			LastPrice varchar(50),
			BuyVolume varchar(50),
			SellVolume varchar(50),
			Open varchar(50),
			Close varchar(50),
			BaseVolume varchar(50),
			BuyBaseVolume varchar(50),
			SellBaseVolume varchar(50),
			timestamp varchar(50)
			)");
			var_dump($query);
		}
		//Check if we need to update
		$query = $db->query('SELECT * FROM cryptopia LIMIT 1');
		while ($row = $query->fetch_assoc()) {
			if ($row['timestamp'] >= $timestamp) {
		        return true;
			}
		}
		//Get full ticker array and write to db
		$curl = new Curl;
		$curl->link = 'https://www.cryptopia.co.nz/api/GetMarkets';
		$request = $curl->curlRequest();
		if ($request) {
			$array = json_decode($request, true);
		} else {
			return false;
		}
		//Delete old table
		$db->query("DELETE FROM cryptopia");
		//Disable autocommit for this transaction
		$db->autocommit(false);
		//Write new data
		foreach ($array['Data'] as $coin) {
		$db->query("INSERT INTO cryptopia (
			TradePairId,
			Label,
			AskPrice,
			BidPrice,
			Low,
			High,
			Volume,
			LastPrice,
			BuyVolume,
			SellVolume,
			Open,
			Close,
			BaseVolume,
			BuyBaseVolume,
			SellBaseVolume,
			timestamp
			) VALUES (
			'" . $coin['TradePairId'] . "', '" .
			$coin['Label'] . "', '" .
			$coin['AskPrice'] . "', '" .
			$coin['BidPrice'] . "', '" .
			$coin['Low'] . "', '" .
			$coin['High'] . "', '" .
			$coin['Volume'] . "', '" .
			$coin['LastPrice'] . "', '" .
			$coin['BuyVolume'] . "', '" .
			$coin['SellVolume'] . "', '" .
			$coin['Open'] . "', '" .
			$coin['Close'] . "', '" .
			$coin['BaseVolume'] . "', '" .
			$coin['BuyBaseVolume'] . "', '" .
			$coin['SellBaseVolume'] . "', '" .
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
	* @var $symbol STRING (btc, eth, nano, etc...)
	*/
	public static function getCoinPrice($symbol) {
        global $db;
	    $src = strtoupper($symbol . '/BTC');
		//Lookup coin in hitbtc database
		$query = $db->query('SELECT * FROM cryptopia WHERE Label="' . $src . '"');
		while ($row = $query->fetch_assoc()) {
		    return $row['BidPrice'];
		}
	    return false;
	}
}
?>