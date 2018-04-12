<?PHP
class PumpMonitor {
	public function detect() {
	    //Database
		global $db;
		//Get settings
		$results = $db->query('SELECT * FROM settings');
		while ($row = $results->fetch_assoc()) {
		    $settings[$row["Setting"]] = $row["Value"];
		}
		$pumpMonUpdateFrequency = $settings["pumpMonUpdateFrequency"];
	    $pumpMonPriceChange = $settings["pumpMonPriceChange"];
	    $pumpMonVolumeChange = $settings["pumpMonVolumeChange"];
		//Curl module intiation
		$curl = new Curl;
		//Log module initiation
		$log = new Log;
		//Pumped coins array to return
		$pumpedCoins = array();
		//Get all data from 'market_summaries' table to know current prices
		$currentData = array();
		$cd = $db->query('SELECT * FROM market_summaries');
		while ($row = $cd->fetch_assoc()) {
		    $currentData[] = $row;
		}
		//Get all data from 'latest_prices' table to know previous prices
		$previousData = array();
		$pd = $db->query('SELECT * FROM latest_prices');
		while ($row = $pd->fetch_assoc()) {
		    $previousData[] = $row;
		}
		//Fill 'latest_prices' table if empty
		if (count($previousData) == 0) {
		    $db->autocommit(false);
			foreach ($currentData as $key => $value) {
			    $timeStamp = time();
				$statement = $db->prepare("INSERT INTO latest_prices (coin, timestamp, price) VALUES (?, ?, ?)");
				$statement->bind_param('sid', $value['MarketName'], $timeStamp, $value['Bid']);
				$statement->execute();
				$statement->close();
			}
			$db->commit();
		    $db->autocommit(true);
		}
		//Autocommit off
		$db->autocommit(false);
		//Scan all coins and check each for pump
		foreach ($currentData as $key => $value) {
		    
		    $timeStamp = time();
		    $marketName = $value['MarketName'];
		    $currentPrice = $value['Bid'];
			$volume = $value['BaseVolume'];
		    $previousPrice = $previousData[$key]['price'];
			$timeDiff = $timeStamp - $previousData[$key]['timestamp'];
			
			//Check if time difference is bigger than $pumpMonUpdateFrequency then we can continue to pump detection
			if ($timeDiff > $pumpMonUpdateFrequency) {
				//After we set all vars and checked time difference, we can update 'latest_prices' table
				$statement = $db->prepare("UPDATE latest_prices SET timestamp = ?, price = ? WHERE coin = ?");
				$statement->bind_param('ids', $timeStamp, $currentPrice, $marketName);
				$statement->execute();
				$statement->close();
				
				//If price difference is more than $pumpMonPriceChange, proceed to volume check
				if (($currentPrice - $previousPrice) / ($previousPrice * 0.01) > $pumpMonPriceChange) {
				    //Start constructing log message
					$message = 'Upd.freq.: ' . $pumpMonUpdateFrequency . ' PrC: ' . $pumpMonPriceChange . ' VolC: ' . $pumpMonVolumeChange . ' ';
				    $message .= $marketName . ' pumped from ' . $previousPrice . ' to ' . $currentPrice . ' ';
				    //Request market history array from Bittrex
					$curl->link = 'https://bittrex.com/api/v1.1/public/getmarkethistory?market=' . $marketName;
					$request = $curl->curlRequest();
					if ($request) {
				        $tradesArray = json_decode($request, true);
				    } else {
					    continue;
					}
					$timeStart = strtotime($tradesArray["result"][0]["TimeStamp"]);
					$timeEnd = $timeStart - $timeDiff;
					$tradeVolume = 0;
					//Calculate trade volume for the given period
				    foreach($tradesArray["result"] as $filledOrder)
					{
				           if($filledOrder["OrderType"] == "BUY")
						{
						    $tradeVolume += $filledOrder["Total"];
						    $tradeTime = strtotime($filledOrder["TimeStamp"]);
							if ($tradeTime < $timeEnd) {break;}
					    }
					}
					//If period volume is more than $pumpMonVolumeChange of base volume
				    if ($tradeVolume / ($volume * 0.01) > $pumpMonVolumeChange)
				    {
						//Continue to construct message
						$message .= 'BV: ' . $volume . ' ' . $pumpMonUpdateFrequency . ' sec vol: ' . $tradeVolume;
						//Log message
						$log->origin = 'Pump monitor';
						$log->type = 'Info';
						$log->message = $message;
						$log->write();
						//Add current trade pair to pumped coins list
						$pumpedCoins[] = $marketName;
					}
				}
			}
		}
	$db->commit();
	$db->autocommit(true);
	return $pumpedCoins;
	}
}