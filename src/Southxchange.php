<?PHP
class Southxchange {
    /**
	* Southxchange.com returned data format:
	* Market	"DASH/BTC"
	* Bid	0.05138771
	* Ask	0.05223
	* Last	0.05208658
	* Variation24Hr	-1.67
	* Volume24Hr	14.57262865
	*
	* Function refreshDatabase updates southxchange database, 
	* downloading all the coins from there to local database
	* 
	* Sample usage:
	* $period = time() - 86400; Update if more than 24 hours passed since last update
	* Southxchange::refreshDatabase($period);
	* 
	* @var timestamp INT
	* If this number is higher than lowest last updated timestamp then update happens
	*/
    public static function refreshDatabase($timestamp) {
	    global $db;
		//Create table if it doesn't exist
		$query = $db->query('SHOW TABLES LIKE "southxchange"');
		$num_rows = $query->num_rows;
        if ($num_rows == 0) {
            $query = $db->query("CREATE TABLE southxchange (
			Market varchar(50),
			Bid varchar(50),
			Ask varchar(50),
			Last varchar(50),
			Variation24Hr varchar(50),
			Volume24Hr varchar(50),
			timestamp varchar(50)
            )");
		}
		//Check if we need to update
		$query = $db->query('SELECT * FROM southxchange LIMIT 1');
		while ($row = $query->fetch_assoc()) {
			if ($row['timestamp'] >= $timestamp) {
		        return true;
			}
		}
		//Get full ticker array and write to db
		$curl = new Curl;
		$curl->link = 'https://www.southxchange.com/api/prices';
		$request = $curl->curlRequest();
		if ($request) {
			$array = json_decode($request, true);
		} else {
			return false;
		}
		//Delete old table
		$db->query("DELETE FROM southxchange");
		//Disable autocommit for this transaction
		$db->autocommit(false);
		//Write new data
		foreach ($array as $coin) {
		$db->query("INSERT INTO southxchange (
			Market,
			Bid,
			Ask,
			Last,
			Variation24Hr,
			Volume24Hr,
			timestamp
			) VALUES (
			'" . $coin['Market'] . "', '" .
			$coin['Bid'] . "', '" .
			$coin['Ask'] . "', '" .
			$coin['Last'] . "', '" .
			$coin['Variation24Hr'] . "', '" .
			$coin['Volume24Hr'] . "', '" .
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
		$query = $db->query('SELECT * FROM southxchange WHERE Market="' . $src . '"');
		while ($row = $query->fetch_assoc()) {
		    return $row['Bid'];
		}
	    return false;
	}
}
?>