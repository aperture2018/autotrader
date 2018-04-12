<?PHP
class Coinmarketcap {
    /**
	* Data format returned by coinmarketcap.com:
	* id	"bitcoin"
	* name	"Bitcoin"
	* symbol	"BTC"
	* rank	"1"
	* price_usd	"8816.82"
	* price_btc	"1.0"
	* 24h_volume_usd	"8083350000.0"
	* market_cap_usd	"149089781154"
	* available_supply	"16909700.0"
	* total_supply	"16909700.0"
	* max_supply	"21000000.0"
	* percent_change_1h	"-1.74"
	* percent_change_24h	"-6.55"
	* percent_change_7d	"-19.87"
	* last_updated	"1520625266"
    *
	* Function refreshDatabase updates coinmarketcap database, 
	* downloading all the coins from there to local database
	* 
	* Sample usage:
	* $period = time() - 86400; Update if more than 24 hours passed since last update
	* Coinmarketcap::refreshDatabase($period);
	* 
	* @var timestamp INT
	* If this number is higher than lowest last updated timestamp then update happens
	*/
    public static function refreshDatabase($timestamp) {
	    global $db;
		//Create table if it doesn't exist
		$query = $db->query('SHOW TABLES LIKE "coinmarketcap"');
		$num_rows = $query->num_rows;
        if ($num_rows == 0) {
            $query = $db->query("CREATE TABLE coinmarketcap (
		    id varchar(50),
			name varchar(50),
			symbol varchar(50),
			rank varchar(50),
			price_usd varchar(50),
			price_btc varchar(50),
			24h_volume_usd varchar(50),
			market_cap_usd varchar(50),
			available_supply varchar(50),
			total_supply varchar(50),
			max_supply varchar(50),
			percent_change_1h varchar(50),
			percent_change_24h varchar(50),
			percent_change_7d varchar(50),
			last_updated varchar(50)
            )");
		}
		//Check if we need to update
		$query = $db->query('SELECT * FROM coinmarketcap LIMIT 1');
		$row = $query->fetch_assoc();
		if ($row['last_updated'] >= $timestamp) {
	        return true;
		}
		//Get full ticker array and write to db
		$curl = new Curl;
		$curl->link = 'https://api.coinmarketcap.com/v1/ticker/?limit=0';
		$request = $curl->curlRequest();
		if ($request) {
			$array = json_decode($request, true);
		} else {
			return false;
		}
		//Delete table "coinmarketcap"
		$db->query("DELETE FROM coinmarketcap");
		$db->autocommit(false);
		$i = 0;
		foreach ($array as $coin) {
		$db->query("INSERT INTO coinmarketcap (
		    id,
			name,
			symbol,
			rank,
			price_usd,
			price_btc,
			24h_volume_usd,
			market_cap_usd,
			available_supply,
			total_supply,
			max_supply,
			percent_change_1h,
			percent_change_24h,
			percent_change_7d,
			last_updated
			) VALUES (
			'" . $coin['id'] . "', '" .
			$coin['name'] . "', '" .
			$coin['symbol'] . "', '" .
			$coin['rank'] . "', '" .
			$coin['price_usd'] . "', '" .
			$coin['price_btc'] . "', '" .
			$coin['24h_volume_usd'] . "', '" .
			$coin['market_cap_usd'] . "', '" .
			$coin['available_supply'] . "', '" .
			$coin['total_supply'] . "', '" .
			$coin['max_supply'] . "', '" .
			$coin['percent_change_1h'] . "', '" .
			$coin['percent_change_24h'] . "', '" .
			$coin['percent_change_7d'] . "', '" .
			time() .
			"')");
		}
		$db->commit();
		$db->autocommit(true);
		return true;
    }
	/**
	* Private function dbQuery returns selected detail of the coin or 'false' on failure
	* @var $symbol STRING coin symbol (btc, eth, nano, etc...)
	* @var $column STRING db column (id, name, price_btc, etc...)
	*/
	private static function dbQuery($symbol, $column) {
	    global $db;
	    $src = strtoupper($symbol);
	    //Lookup requested column in database
		$query = $db->query('SELECT ' . $column . ' FROM coinmarketcap WHERE symbol="' . $src . '"');
		while ($row = $query->fetch_assoc()) {
		    return $row[$column];
		}
	    return false;
	}
	/**
	* Function getCoinPrice returns price of the coin or 'false' on failure
	* @var $symbol STRING (btc, eth, nano, etc...)
	*/
	public static function getCoinPrice($symbol) {
        return Coinmarketcap::dbQuery($symbol, 'price_btc');
	}
	/**
	* Function getCoinName returns full name of the coin or 'false' on failure
	* @var $symbol STRING (btc, eth, nano, etc...)
	*/
	public static function getCoinName($symbol) {
        return Coinmarketcap::dbQuery($symbol, 'name');
	}
}
?>