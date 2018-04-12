<?PHP
class Stocksexchange {
    /**
	* Stocks.exchange returned data format:
	* buy	"0.00009781"
	* sell	"0.00013238"
	* market_name	"STEX_BTC"
	* updated_time	1520621401
	* server_time	1520621401
	*
	* Function refreshDatabase updates stocksexchange database, 
	* downloading all the coins from there to local database
	* 
	* Sample usage:
	* $period = time() - 86400; Update if more than 24 hours passed since last update
	* Stocksexchange::refreshDatabase($period);
	* 
	* @var timestamp INT
	* If this number is higher than lowest last updated timestamp then update happens
	*/
    public static function refreshDatabase($timestamp) {
	    global $db;
		//Create table if it doesn't exist
		$query = $db->query('SHOW TABLES LIKE "stocksexchange"');
		$num_rows = $query->num_rows;
        if ($num_rows == 0) {
            $query = $db->query("CREATE TABLE stocksexchange (
			buy varchar(50),
			sell varchar(50),
			market_name varchar(50),
			timestamp varchar(50)
            )");
		}
		//Check if we need to update
		$query = $db->query('SELECT * FROM stocksexchange LIMIT 1');
		while ($row = $query->fetch_assoc()) {
			if ($row['timestamp'] >= $timestamp) {
		        return true;
			}
		}
		//Get full ticker array and write to db
		$curl = new Curl;
		$curl->link = 'https://stocks.exchange/api2/prices';
		$request = $curl->curlRequest();
		if ($request) {
			$array = json_decode($request, true);
		} else {
			return false;
		}
		//Delete old table
		$db->query("DELETE FROM stocksexchange");
		//Disable autocommit for this transaction
		$db->autocommit(false);
		//Write new data
		foreach ($array as $coin) {
		$db->query("INSERT INTO stocksexchange (
            buy,
			sell,
			market_name,
			timestamp
			) VALUES (
			'" . $coin['buy'] . "', '" .
			$coin['sell'] . "', '" .
			$coin['market_name'] . "', '" .
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
	    $src = strtoupper($symbol . '_BTC');
		//Lookup coin in hitbtc database
		$query = $db->query('SELECT * FROM stocksexchange WHERE market_name="' . $src . '"');
		while ($row = $query->fetch_assoc()) {
		    return $row['buy'];
		}
	    return false;
	}
}
?>