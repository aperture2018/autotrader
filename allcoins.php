<?PHP
require 'config.inc.php';
session_start();
$coin = new Coin;

// Update databases
$refreshPeriod = time() + 3600;
Coinmarketcap::refreshDatabase($refreshPeriod);
Cryptopia::refreshDatabase($refreshPeriod);
Hitbtc::refreshDatabase($refreshPeriod);
Southxchange::refreshDatabase($refreshPeriod);
Stocksexchange::refreshDatabase($refreshPeriod);

// Tags
// Create tag array for this session
if (!isset($_SESSION['tags'])) {
	$_SESSION['tags'] = array();
}
// GET tag
if (isset($_GET["tag"])) {
	if (!in_array($_GET['tag'], $_SESSION['tags'])) {
        $_SESSION['tags'][] = $_GET['tag'];
	}
}
// GET tag removal request
if (isset($_GET['removetag'])) {
    unset($_SESSION['tags'][array_search($_GET['removetag'], $_SESSION['tags'])]);
}
$coin->activeTags = $_SESSION['tags'];

// Delete item
if (isset($_GET["del"])) {
    $db->query('DELETE FROM allcoins WHERE id="' . $_GET['del'] . '"');
	$coin->message = "Coin deleted";
}

// Show coin details (on click)
if (isset($_GET["id"])) {
    $coin->getCoinData($_GET["id"]);
	$coin->showHeader();
	$coin->showForm();
	$coin->showFooter();
	exit();
}

// Add or update coin (database)
if (isset($_POST['id'])) {
    // Update coin
       if ($_POST["id"] != '') {
           $coin->updateCoin();
	       $coin->showHeader();
		   $coin->getCoinData($_POST["id"]);
	       $coin->showForm();
	       $coin->showFooter();
		   exit();
	} else {
	    // Add coin
		if ($_POST['coinSymbol'] != '') {
		    $coin->addCoin();
		} else {
			$coin->showHeader();
			$coin->getPostData();
		    $coin->showForm();
		    $coin->showFooter();
			exit();
		}
	}
}

// Add coin (form)
        if (isset($_GET["addcoin"])) {
			$coin->showHeader();
		    $coin->showForm();
		    $coin->showFooter();
			exit();
		}

$coin->showHeader();
//$coin->showForm();
//$coin->showAlgo();
$coin->showTags();
$coin->showCoins();
$coin->showFooter();


###############################


class Coin
{
    public $id = '';
    //Main details
    public $coinName = "";
	public $coinSymbol = "";
	public $launchDate = "";
	//Holding details
	public $amount = 0;
	public $buyPrice = 0;
	public $buyDate = 0;
	//Mining details
	public $coinAlgo = "";
	public $myHashrate = 0;
	public $myHashrateMod = "H/s";
	public $blockTime = 0;
	public $blockReward = 0;
	public $netHashrate = 0;
	public $netHashrateMod = "Mh/s";
	//Ann/website links, exchanges, tags, rating
	public $bitcointalkAnn = "";
	public $website = "";
	public $tags = "";
	public $links = "";
	public $rating = 0;
	public $comment = "";
	
	public $message;
	public $activeTags = array();
    
	public function showTags() {
	    global $db;
		$tags = array();
		$query = $db->query('SELECT tags FROM allcoins');
		while ($row = $query->fetch_assoc()) {
		    $tagsArr = explode(',', $row['tags']);
			foreach($tagsArr as $tag) {
			    if (!isset($tags[$tag])) {
				    $tags[$tag] = 0;
				}
			    $tags[$tag]++;
			}
		}
		$prc = max($tags)*0.1;
		echo '<div id = "tags">';
		foreach ($tags as $tag => $tagFreq) {
		    $tagClass = floor($tagFreq/$prc);
		    echo '<div class="tag"><span class="tagClass' . $tagClass . '"><a href="allcoins.php?tag=' . $tag . '">' . $tag . '</a></span></div>';
		}
		echo '</div>';
	}
    
	public function showForm()
	{
		echo '
		<div id="coin_form">
		<form action="allcoins.php" method="post">
		  <fieldset>
		   <br>
		    <legend>Coin details</legend>
			Coin name: <input type = "text" name = "coinName" size="14" value="' . $this->coinName . '"><br>
			Coin symbol: <input type = "text" name = "coinSymbol" size="12" value="' . $this->coinSymbol . '"><br>
			Launch date: <input type = "text" name = "launchDate" size="12" value="' . $this->launchDate . '"><br>
		  </fieldset>
		  <fieldset>
		   <br>
		    <legend>Holding</legend>
			Amount: <input type = "text" name = "amount" size="12" value="' . $this->amount . '"><br>
			Buy price: <input type = "text" name = "buyPrice" size="14" value="' . $this->buyPrice . '"><br>
			Buy date: <input type = "text" name = "buyDate" size="12" value="' . $this->buyDate . '"><br>
			</fieldset>
		  <fieldset>
		   <br>
		    <legend>Mining details</legend>
		    Algorithm: <input type = "text" name = "coinAlgo" size="12" value="' . $this->coinAlgo . '"><br>
			My hashrate: <input type = "text" name = "myHashrate" size="12" value="' . $this->myHashrate . '">
			<select name = "myHashrateMod">
			<option value="H/s" ';
			if ($this->myHashrateMod == "H/s") {echo 'selected="selected"';}
			echo '>H/s</option>
			<option value="Kh/s" ';
			if ($this->myHashrateMod == "Kh/s") {echo 'selected="selected"';}
			echo '>Kh/s</option>
			<option value="Mh/s" ';
			if ($this->myHashrateMod == "Mh/s") {echo 'selected="selected"';}
			echo '>Mh/s</option>
			</select><br>
			Block time: <input type = "text" name = "blockTime" size="12" value="' . $this->blockTime . '"><br>
			Block reward: <input type = "text" name = "blockReward" size="12" value="' . $this->blockReward . '"><br>
			Netw. hashrate: <input type = "text" name = "netHashrate" size="12" value="' . $this->netHashrate . '">
			<select name = "netHashrateMod">
			<option value="Kh/s" ';
			if ($this->netHashrateMod == "Kh/s") {echo 'selected="selected"';}
			echo '>Kh/s</option>
			<option value="Mh/s" ';
			if ($this->netHashrateMod == "Mh/s") {echo 'selected="selected"';}
			echo '>Mh/s</option>
			<option value="Gh/s" ';
			if ($this->netHashrateMod == "Gh/s") {echo 'selected="selected"';}
			echo '>Gh/s</option>
			<option value="Th/s" ';
			if ($this->netHashrateMod == "Th/s") {echo 'selected="selected"';}
			echo '>Th/s</option>
			</select><br><br>';
		  echo '</fieldset>
		  <fieldset>
		    <legend>Links</legend>
			ANN: <input type = "text" name = "bitcointalkAnn" size="28" value="' . $this->bitcointalkAnn . '"> <a href="' . $this->bitcointalkAnn . '" target="_blank">Open</a><br>
			Website: <input type = "text" name = "website" size="24" value="' . $this->website . '"> <a href="' . $this->website . '" target="_blank">Open</a><br>
			Tags:<br><textarea rows="1" cols="26" name="tags">' . $this->tags . '</textarea><br>
			Links:<br><textarea rows="1" cols="26" name="links">' . $this->links . '</textarea><br>
			Comment:<br><textarea rows="1" cols="26" name="comment">' . $this->comment . '</textarea><br>
			Rating (1-10): <input type = "text" name = "rating" size="2" value="' . $this->rating . '"><br>
			</fieldset>
		  <input type = "hidden" name = "id" value = "' . $this->id . '">
		  <input type = "submit" name = "submit" value = "Submit">
		</form>
		<form action="allcoins.php" method="post">
		<input type = "submit" name = "submit" value = "Back">
		</form>
		</div>
		';
	}
	
	public function showAlgo() {
	    global $db;
		//Rating color chart
		$ratingColors = array(
		0 => '#eaeaea',
		1 => '#c6c6c6',
		2 => '#006699',
		3 => '#9999cc',
		4 => '#ccffcc',
		5 => '#ccffff',
		6 => '#cc99cc',
		7 => '#ff9999',
		8 => '#ff9966',
		9 => '#ff6666',
		10 => '#ff3300',
		);
		//Show available algo
		$algoString = '<div id="mining_algos">';
		$query = $db->query('SELECT DISTINCT coinAlgo FROM allcoins');
		while ($row = $query->fetch_assoc()) {
		    $allAlgos[] = $row;
		}
		foreach ($allAlgos as $algo) {
		    if ($algo["coinAlgo"] == "") {
			    $algoName = "Unspecified";
			} else {
			    $algoName = $algo["coinAlgo"];
			}
		    $algoString .= '<div class="mining_algo_element">' . $algoName . '</div>';
			//Show all coins for current algo
			$query = $db->query('SELECT * FROM allcoins WHERE coinAlgo = "' . $algo["coinAlgo"] . '"');
			while ($row = $query->fetch_assoc()) {
			    //If this coin is selected, highlight it in the list
				$coinSymbolClass = '';
			    if ($this->id == $row['id']) {
				        $coinSymbolClass = 'mining_coin_selected';
					}
		        $algoString .= '<a href="allcoins.php?id=' . $row['id'] . '" class="' . $coinSymbolClass . '">' . $row['coinSymbol'] . '</a>';
				if ($row['rating'] > 0) {
				    $algoString .= '&nbsp;<span style="color: #ffffff; background-color: ' . $ratingColors[$row['rating']] . ';">&nbsp;' . $row['rating'] . '&nbsp;</span>';
				}
				$algoString .= '&nbsp;<span class="deleteButton">[<a href="allcoins.php?del=' . $row['id'] . '">x</a>]</span> ';
		    }
		    $algoString .= '<br>';
		}
		$algoString .= '</div>';
		echo $algoString;
	}
	
	public function showHeader() {
	    echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
		<html><head><title></title><link rel="stylesheet" type="text/css" href="css/allcoins.css"></head><body>
		<div class="content"><div class="header"></div><div class="menu"><a href="index.php">Back</a> <a href="allcoins.php?addcoin">Add coin</a></div>
		';
		//Show tags
		if ($this->activeTags != '') {
		    foreach ($this->activeTags as $tag) {
			    echo '<div class="activeTag">' . $tag . '&nbsp<a href="allcoins.php?removetag=' . $tag . '">x</a></div>';
			}
		}
		echo '<div style="clear: both;"></div>';
		//Show message
		if ($this->message != "") {
		    echo '<div class="message">' . $this->message . '</div>';
		}
    }
	
    public function showFooter() {
        echo '</body></html>';
    }
	public function getCoinData($id) {
	    global $db;
		$query = $db->query('SELECT * FROM allcoins WHERE id = "' . $id . '"');
		$row = $query->fetch_assoc();
	    
		$this->id = $id;
	    //Main details
	    $this->coinName = $row["coinName"];
		$this->coinSymbol = $row["coinSymbol"];
		$this->launchDate = $row['launchDate'];
		//Holding details
		$this->amount = $row['amount'];
		$this->buyPrice = $row['buyPrice'];
		$this->buyDate = $row['buyDate'];
		//Mining details
		$this->coinAlgo = $row["coinAlgo"];
		$this->myHashrate = $row["myHashrate"];
		$this->myHashrateMod = $row["myHashrateMod"];
		$this->blockTime = $row["blockTime"];
		$this->blockReward = $row["blockReward"];
		$this->netHashrate = $row["netHashrate"];
		$this->netHashrateMod = $row["netHashrateMod"];
		//Ann/website links, exchanges, tags, rating
		$this->bitcointalkAnn = $row["bitcointalkAnn"];
		$this->website = $row["website"];
		$this->tags = $row['tags'];
		$this->links = $row['links'];
		$this->rating = $row["rating"];
		$this->comment = $row["comment"];
	}
	public function getPostData() {
	    $this->id = $_POST['id'];
	    //Main details
	    $this->coinName = $_POST['coinName'];
		$this->coinSymbol = $_POST['coinSymbol'];
		$this->launchDate = $_POST['launchDate'];
		//Holding details
		$this->amount = $_POST['amount'];
		$this->buyPrice = $_POST['buyPrice'];
		$this->buyDate = $_POST['buyDate'];
		//Mining details
		$this->coinAlgo = $_POST['coinAlgo'];
		$this->myHashrate = $_POST['myHashrate'];
		$this->myHashrateMod = $_POST['myHashrateMod'];
		$this->blockTime = $_POST['blockTime'];
		$this->blockReward = $_POST['blockReward'];
		$this->netHashrate = $_POST['netHashrate'];
		$this->netHashrateMod = $_POST['netHashrateMod'];
		//Ann/website links, exchanges, tags, rating
		$this->bitcointalkAnn = $_POST['bitcointalkAnn'];
		$this->website = $_POST['website'];
		$this->tags = $_POST['tags'];
		$this->links = $_POST['links'];
		$this->rating = $_POST['rating'];
		$this->comment = $_POST['comment'];
	}
	public function updateCoin() {
	    global $db;
		$id = trim($_POST['id']);
		//Update existing coin record
		$statement = $db->prepare('UPDATE allcoins SET 
		coinSymbol = ?,
		coinName = ?,
		coinAlgo = ?,
		launchDate = ?,
        amount = ?,
        buyPrice = ?,
        buyDate = ?,
		myHashrate = ?,
		myHashrateMod = ?,
		blockTime = ?,
		blockReward = ?,
		netHashrate = ?,
		netHashrateMod = ?,
		bitcointalkAnn = ?,
		website = ?,
		tags = ?,
        links = ?,
		rating = ?,
		comment = ?
		WHERE id = ?');
		
		$statement->bind_param('sssiddiisiddsssssssi',
		
		$_POST['coinSymbol'],
		$_POST['coinName'],
		$_POST['coinAlgo'],
		$_POST['launchDate'],
		
        $_POST['amount'],
        $_POST['buyPrice'],
        $_POST['buyDate'],
		
		$_POST['myHashrate'],
		$_POST['myHashrateMod'],
		$_POST['blockTime'],
		$_POST['blockReward'],
		$_POST['netHashrate'],
		$_POST['netHashrateMod'],
		
		$_POST['bitcointalkAnn'],
		$_POST['website'],
        $_POST['tags'],
        $_POST['links'],
		$_POST['rating'],
		$_POST['comment'],
		$id
		);
		
		$statement->execute();
		$statement->close();
		$this->message = "Coin updated successfully";
	}
	public function addCoin() {
	    global $db;
		$coin = strtoupper(trim($_POST['coinSymbol']));
		
		//Check if the coin symbol is already in db
		$query = $db->query('SELECT * FROM allcoins WHERE coinSymbol = "' . $coin . '"');
		$result = $query->fetch_assoc();
		
		if (isset($result)) {
		    $this->message = 'Coin exists';
		    return;
		}
		//Add coin to 'allcoins' table
		$statement = $db->prepare('INSERT INTO allcoins (
		coinSymbol,
		coinName,
		coinAlgo,
		launchDate,
        amount,
        buyPrice,
        buyDate,
		myHashrate,
		myHashrateMod,
		blockTime,
		blockReward,
		netHashrate,
		netHashrateMod,
		bitcointalkAnn,
		website,
		tags,
		links,
		rating,
		comment) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
		$statement->bind_param('sssiddiisiddsssssss',
		
		$coin,
		$_POST['coinName'],
		$_POST['coinAlgo'],
		$_POST['launchDate'],
		
        $_POST['amount'],
        $_POST['buyPrice'],
        $_POST['buyDate'],
		
		$_POST['myHashrate'],
		$_POST['myHashrateMod'],
		$_POST['blockTime'],
		$_POST['blockReward'],
		$_POST['netHashrate'],
		$_POST['netHashrateMod'],
		
		$_POST['bitcointalkAnn'],
		$_POST['website'],
        $_POST['tags'],
        $_POST['links'],
		$_POST['rating'],
		$_POST['comment']
		);
		
		$statement->execute();
		$statement->close();
		$this->message = "Coin added successfully";
    }
	
	private function calcReward($coinArray) {
	    //If needed values are not set, return "n/a"
		if ($coinArray['myHashrate'] == 0 || $coinArray['blockTime'] == 0 || $coinArray['blockReward'] == 0 || $coinArray['netHashrate'] == 0) {
		    return false;
		}
		//Set hashrate modifier values
		$modifier = array(
		"H/s" => 1,
		"Kh/s" => 1000,
		"Mh/s" => 1000000,
		"Gh/s" => 1000000000,
		"Th/s" => 1000000000000
		);
		//Calculate nethashrate
		$netHashrate = $coinArray['netHashrate'] * $modifier[$coinArray['netHashrateMod']];
		//Calculate my hashrate proportion
		$myHashrate = ($coinArray['myHashrate'] * $modifier[$coinArray['myHashrateMod']]) / $netHashrate;
		//Calculate total network block reward for 24h
		$emission = $coinArray['blockReward'] * (86400 / $coinArray['blockTime']);
		//Calculate my expected reward for 24h
		$myReward = floor($myHashrate * $emission);
		return $myReward;
	}
	
	public function showCoins() {
		global $db;
		$query = $db->query('SELECT * FROM allcoins ORDER BY coinName');
		echo '<div id="coins_list">';
	    while ($coinArr = $query->fetch_assoc()) {
		    $showCoin = true;
		    //Check tags
			if (count($this->activeTags) > 0) {
			    $tagsArr = explode(',', $coinArr['tags']);
				foreach ($this->activeTags as $activeTag) {
				    if (!in_array($activeTag, $tagsArr)) {
					    $showCoin = false;
				    }
				}
            }
			if ($showCoin) {
			    $this->showCoin($coinArr);
            }
		}
		echo '</div>';
	}
	
	private function showCoin($coinArr) {
		global $db;
	    global $htmlPath;
		$coinSymbol = strtolower($coinArr['coinSymbol']);
		
		
		//Temp
		$coinPriceBtc = Coinmarketcap::getCoinPrice($coinSymbol);
		if (!isset($coinPriceBtc)) {
		    $coinPriceBtc = Cryptopia::getCoinPrice($coinSymbol);
		}
		if (!$coinPriceBtc) {
		    $coinPriceBtc = Hitbtc::getCoinPrice($coinSymbol);
		}
		if (!$coinPriceBtc) {
		    $coinPriceBtc = Southxchange::getCoinPrice($coinSymbol);
		}
		if (!$coinPriceBtc) {
		    $coinPriceBtc = Stocksexchange::getCoinPrice($coinSymbol);
		}
		
		
		
		$results = $db->query('SELECT * FROM settings');
		while ($row = $results->fetch_assoc())
		{
		    $settings[$row["Setting"]] = $row["Value"];
		}
		//Settings
		$bitcoinPrice = $settings['bitcoinPrice'];
		$coinPriceUsd = $coinPriceBtc * $bitcoinPrice;
		
		//Check if image exists and fetch if needed
        Cryptocompare::fetchCoinImage($coinArr['coinSymbol']);
		
	    echo '<div class="coin"><a href="allcoins.php?id=' . $coinArr['id'] . '">';
		
		$imagePath = $htmlPath . 'images/' . $coinSymbol . '.png';
		$imgNm = $coinSymbol;
		if (file_exists($imagePath)) {
		    echo '<img src="images/' . $imgNm . '.png" align="left">';
		}
		echo $coinArr['coinName'] . ' (' . $coinArr['coinSymbol'] . ')</a>';
		echo ' <span class="deleteButton">[<a href="allcoins.php?del=' . $coinArr['id'] . '">x</a>]</span><br>';
		
        echo '<span class="coin_price">';
		
		if ($coinPriceUsd > 0) {
		    echo '$' . round($coinPriceUsd, 4) . '<br>';
		}
		if ($coinPriceBtc > 0) {
	        echo rtrim(number_format($coinPriceBtc,9), '0') . '&nbsp;';
		}
		if (isset($cmc["percent_change_24h"])) {
	        if ($cmc["percent_change_24h"] < 0) {$color = "Red";} else {$color = "Green";}
	        echo "<span style=\"color:" . $color . "\">" . $cmc["percent_change_24h"] . "%</span><br>";
		}
		
		//Mining info block
		if (in_array('mining', $this->activeTags)) {
		    //Calculate mining reward in coins
		    $rewardCoins = $this->calcReward($coinArr);
			if ($rewardCoins) {
			    echo '<br>' . $rewardCoins . ' ' . $coinArr['coinSymbol'] . ' / day';
			    //Calculate mining reward in usd if coin price is available
			    if ($coinPriceUsd > 0) {
			        $rewardUsd = round($rewardCoins * $coinPriceUsd, 2);
					echo ' ($' . $rewardUsd . ')';
		        }
			}
		}
		
		
		
		
		
		
		
		/*
		if (isset($cmc["percent_change_7d"])) {
	        if ($cmc["percent_change_7d"] < 0) {$color = "Red";} else {$color = "Green";}
	        echo "7d change: <span style=\"color:" . $color . "\">" . $cmc["percent_change_7d"] . "%</span><BR>";
		}
		*/
		echo '</span>';
		
		if (isset($cmc["id"])) {
	        echo '<br><span class="link_cmc"><a href="https://coinmarketcap.com/currencies/' . $cmc["id"] . '/" target="_blank">Coinmarketcap</a></span>';
		}
		/*
	    echo '<form action="" method="post">
		<input type = "hidden" name = "action" value = "edit">
		<input type = "hidden" name = "id" value = "' . $coinArr['id'] . '">
		<input type = "submit" name = "submit" value = "Edit"></form>
		';
		*/
		
		
	    echo '</div>';
	}
	
	public function showCoinDetailed() {
	
	}
}
?>