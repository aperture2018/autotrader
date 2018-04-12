<?PHP
class Hitbtc {
    /**
	* Hitbtc.com returned data format:
	* ask	"0.0000003612"
	* bid	"0.0000003611"
	* last	"0.0000003612"
	* open	"0.0000003748"
	* low	"0.0000003390"
	* high	"0.0000003780"
	* volume	"1138348900"
	* volumeQuote	"405.36927183"
	* timestamp	"2018-03-09T17:34:33.206Z"
	* symbol	"BCNBTC"
	*
	* Function refreshDatabase updates hitbtc database, 
	* downloading all the coins from there to local database
	* 
	* Sample usage:
	* $period = time() - 86400; Update if more than 24 hours passed since last update
	* Hitbtc::refreshDatabase($period);
	* 
	* @var timestamp INT
	* If this number is higher than lowest last updated timestamp then update happens
	*/
    public static function refreshDatabase($timestamp) {
	    global $db;
		//Create table if it doesn't exist
		$query = $db->query('SHOW TABLES LIKE "hitbtc"');
		$num_rows = $query->num_rows;
        if ($num_rows == 0) {
            $query = $db->query("CREATE TABLE hitbtc (
			ask	varchar(50),
			bid	varchar(50),
			last varchar(50),
			open varchar(50),
			low varchar(50),
			high varchar(50),
			volume varchar(50),
			volumeQuote varchar(50),
			timestamp varchar(50),
			symbol varchar(50)
            )");
		}
		//Check if we need to update
		$query = $db->query('SELECT * FROM hitbtc LIMIT 1');
		while ($row = $query->fetch_assoc()) {
			if ($row['timestamp'] >= $timestamp) {
		        return true;
			}
		}
		//Get full ticker array and write to db
		$curl = new Curl;
		$curl->link = 'https://api.hitbtc.com/api/2/public/ticker';
		$request = $curl->curlRequest();
		if ($request) {
			$array = json_decode($request, true);
		} else {
			return false;
		}
		//Delete old table
		$db->query("DELETE FROM hitbtc");
		//Disable autocommit for this transaction
		$db->autocommit(false);
		//Write new data
		foreach ($array as $coin) {
		$db->query("INSERT INTO hitbtc (
			ask,
			bid,
			last,
			open,
			low,
			high,
			volume,
			volumeQuote,
			timestamp,
			symbol
			) VALUES (
			'" . $coin['ask'] . "', '" .
			$coin['bid'] . "', '" .
			$coin['last'] . "', '" .
			$coin['open'] . "', '" .
			$coin['low'] . "', '" .
			$coin['high'] . "', '" .
			$coin['volume'] . "', '" .
			$coin['volumeQuote'] . "', '" .
			time() . "', '" .
			$coin['symbol'] .
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
	    $src = strtoupper($symbol . 'BTC');
		//Lookup coin in hitbtc database
		$query = $db->query('SELECT * FROM hitbtc WHERE symbol="' . $src . '"');
		while ($row = $query->fetch_assoc()) {
		    return $row['bid'];
		}
	    return false;
	}
}
?>