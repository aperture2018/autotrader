<?PHP
class Cryptocompare {
    /**
	* Response	"Success"
	* Message	"Coin list succesfully returned! This api is moving to https://min-api.cryptocompare.com/data/all/coinlist, please change the path."
	* BaseImageUrl	"https://www.cryptocompare.com"
	* BaseLinkUrl	"https://www.cryptocompare.com"
	* DefaultWatchlist	
	* 	CoinIs	"1182,7605,5038,24854,3807,3808,202330,5324,5031,20131"
	* 	Sponsored	""
	* Data	
	* 	42	
	* 		Id	"4321"
	* 		Url	"/coins/42/overview"
	* 		ImageUrl	"/media/12318415/42.png"
	* 		Name	"42"
	* 		Symbol	"42"
	* 		CoinName	"42 Coin"
	* 		FullName	"42 Coin (42)"
	* 		Algorithm	"Scrypt"
	* 		ProofType	"PoW/PoS"
	* 		FullyPremined	"0"
	*    	TotalCoinSupply	"42"
	* 		PreMinedValue	"N/A"
	* 		TotalCoinsFreeFloat	"N/A"
	* 		SortOrder	"34"
	* 		Sponsored	false
	* 		IsTrading	true
    *
	* Function refreshDatabase updates cryptocompare database, 
	* downloading all the coins from there to local database
	* 
	* Sample usage:
	* $period = time() - 86400; Update if more than 24 hours passed since last update
	* Cryptocompare::refreshDatabase($period);
	* 
	* @var timestamp INT
	* If this number is higher than lowest last updated timestamp then update happens
	*/
    public static function refreshDatabase($timestamp) {
	    global $db;
		//Create table if it doesn't exist
		$query = $db->query('SHOW TABLES LIKE "cryptocompare"');
		$num_rows = $query->num_rows;
        if ($num_rows == 0) {
            $query = $db->query("CREATE TABLE cryptocompare (
			Id varchar(50),
			Url varchar(50),
			ImageUrl varchar(50),
			Name varchar(50),
			Symbol varchar(50),
			CoinName varchar(50),
			FullName varchar(50),
			Algorithm varchar(50),
			ProofType varchar(50),
			timestamp varchar(50)
            )");
		}
		//Check if we need to update
		$query = $db->query('SELECT * FROM cryptocompare LIMIT 1');
		$row = $query->fetch_assoc();
		if ($row['timestamp'] >= $timestamp) {
	        return true;
		}
		//Get full ticker array and write to db
		$curl = new Curl;
		$curl->link = 'https://min-api.cryptocompare.com/data/all/coinlist';
		$request = $curl->curlRequest();
		if ($request) {
			$array = json_decode($request, true);
		} else {
			return false;
		}
		//Delete table "coinmarketcap"
		$db->query("DELETE FROM cryptocompare");
		$db->autocommit(false);
		foreach ($array['Data'] as $coin) {
		//Protect from missing ImageUrl field
		$coinImageUrl = '';
		if (isset($coin['ImageUrl'])) {
		    $coinImageUrl = $coin['ImageUrl'];
		}
		$db->query("INSERT INTO cryptocompare (
			Id,
			Url,
			ImageUrl,
			Name,
			Symbol,
			CoinName,
			FullName,
			Algorithm,
			ProofType,
			timestamp
			) VALUES (
			'" . $coin['Id'] . "', '" .
			$coin['Url'] . "', '" .
			$coinImageUrl . "', '" .
			$coin['Name'] . "', '" .
			$coin['Symbol'] . "', '" .
			$coin['CoinName'] . "', '" .
			$coin['FullName'] . "', '" .
			$coin['Algorithm'] . "', '" .
			$coin['ProofType'] . "', '" .
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
		$query = $db->query('SELECT ' . $column . ' FROM cryptocompare WHERE Symbol="' . $src . '"');
		while ($row = $query->fetch_assoc()) {
		    return $row[$column];
		}
	    return false;
	}
	/**
	* Function getCoinImageUrl returns relative URL of the coin image at cryptocompare.com or 'false' on failure
	* @var $symbol STRING (btc, eth, nano, etc...)
	*/
	private static function getCoinImageUrl($symbol) {
        return Cryptocompare::dbQuery($symbol, 'ImageUrl');
	}
	/**
	* Function getCoinName returns full name of the coin or 'false' on failure
	* @var $symbol STRING (btc, eth, nano, etc...)
	*/
	public static function getCoinName($symbol) {
        return Cryptocompare::dbQuery($symbol, 'CoinName');
	}
	/**
	* Function fetchCoinImage fetches coin logo image from cryptocompare.com and writes it to file
	* @var $symbol STRING coin symbol (btc, eth, nano, etc...)
	*/
	public static function fetchCoinImage($symbol)
	{
	    global $htmlPath;
		$coinName = strtoupper($symbol);
		$coinFileName = strtolower($symbol);
		//Fetch coin image from cryptocompare.com
		if (!file_exists($htmlPath . "images/" . $coinFileName . ".png")) {
            $coinImageUrl = Cryptocompare::getCoinImageUrl($coinName);
			if (!$coinImageUrl) {
			    return false;
			}
			$curl = new Curl();
			$curl->link = "https://cryptocompare.com" . $coinImageUrl;
			$fileString = $curl->curlRequest();
			//Quit if failed to retrieve coin image
			if (!$fileString || !stripos($fileString, 'png')) {
			    return false;
			}
			//Check if the returned string contains image tag
            if ($curl->contentType != 'image/png') {
			    return false;
			}
            //Write coin image to file on success
			$coinFilePath = $htmlPath . "images/". $coinFileName . ".png";
			$fh = fopen($coinFilePath, 'wb');
			fwrite($fh, $fileString);
			fclose($fh);
			return true;
		}
		return false;
	}
}
?>