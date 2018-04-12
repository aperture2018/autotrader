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
}
?>