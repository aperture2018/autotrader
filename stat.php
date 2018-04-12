<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">

<html>
<head>
	<title></title>
</head>
<style>
.content {
width:800px;
margin:0 auto;
margin-top: 100px;
font-family: Verdana, Arial;
}
.market {
width: 200px;
padding: 10px;
display: inline-block;
vertical-align: top;
}
.header {
width: 600px;
padding: 10px;
clear: both;
font-family: Verdana, Arial;
font-size: 26px;
}
.sold {
width: 800px;
font-family: Verdana, Arial;
font-size: 10px;
}
.sellDate {
font-family: Verdana, Arial;
font-size: 10px;
margin-left: 10px;
}
.hold {
font-family: Verdana, Arial;
font-size: 10px;
color: Gray;
}
.tradeInfo {
font-family: Courier New;
font-size: 12px;
}
.menu {
width: 600px;
clear: both;
margin-bottom: 20px;
font-family: Verdana, Arial;
font-size: 16px;
}
.stat {
font-family: Verdana, Arial;
font-size: 16px;
}
A {
text-decoration: none;
color: Black;
}
A:hover {

}
A.tooltip {

}
A.tooltip span {
display: none;
}
A.tooltip:hover span {
position: relative;
top: 10px;
left: 0px;
display: block;
min-width: 50px;
max-width: 200px;
color: black;
background-color: #ffffff;
border: 1px solid #000000;
border-radius: 10px;
padding: 5px;
font-family: Verdana, Arial;
font-size: 10px;
text-decoration: none;
}
IMG {
margin-top: 5px;
padding-right: 5px;
float: left;
vertical-align: top;
}
</style>
<body>

<div class="content">
<div class="header"></div>
<div class="menu"><a href="index.php">Return</a></div>

<?PHP
require 'config.inc.php';
session_start();
$auth = new Auth();
$auth->checkUser();

//Fetch current bitcoin price
//Get current Bitcoin price
$qry = $db->query('SELECT Bid FROM bittrex WHERE MarketName = "USDT-BTC"');
while ($res = $qry->fetch_assoc()) {
    $btcPrice = $res["Bid"];
}

//Calculate today, week, month and all time profits

//Today
$profit = $profitTrades = $unprofitTrades = 0;
$results = $db->query('SELECT * FROM coins WHERE sellDate IS NOT NULL AND DATE(from_unixtime(sellDate)) = CURRENT_DATE');
while ($row = $results->fetch_assoc())
{
    $pr = (($row["buyAmount"] / $row["buyPrice"]) * $row["sellPrice"]) - ($row["buyAmount"]);
	$profit += $pr;
	if ($pr >= 0) {$profitTrades++;} else {$unprofitTrades++;};
}
$results = $db->query('SELECT * FROM program WHERE sellDate IS NOT NULL AND DATE(from_unixtime(sellDate)) = CURRENT_DATE');
while ($row = $results->fetch_assoc())
{
    //echo "P " . $row["coin"] . "<BR>";
    $pr = (($row["buyAmount"] / $row["buyPrice"]) * $row["sellPrice"]) - ($row["buyAmount"]);
	$profit += $pr;
	if ($pr >= 0) {$profitTrades++;} else {$unprofitTrades++;};
}
$profitFiat = round($profit * $btcPrice);
echo '<div class="stat">Today: ';
if ($unprofitTrades > 0) {echo '<span style="color: Red;">' . $unprofitTrades . '</span> ';}
echo '<span style="color: Green;">' . $profitTrades . '</span> ' . rtrim(number_format($profit,5), '0') . ' $' . $profitFiat . '</div>';
'</div>';

//Yesterday
$profit = $profitTrades = $unprofitTrades = 0;
$results = $db->query('SELECT * FROM coins WHERE sellDate IS NOT NULL AND DATE(from_unixtime(sellDate)) =  DATE_SUB(CURRENT_DATE,INTERVAL 1 DAY)');
while ($row = $results->fetch_assoc())
{
    $pr = (($row["buyAmount"] / $row["buyPrice"]) * $row["sellPrice"]) - ($row["buyAmount"]);
	$profit += $pr;
	if ($pr >= 0) {$profitTrades++;} else {$unprofitTrades++;};
}
$results = $db->query('SELECT * FROM program WHERE sellDate IS NOT NULL AND DATE(from_unixtime(sellDate)) =  DATE_SUB(CURRENT_DATE,INTERVAL 1 DAY)');
while ($row = $results->fetch_assoc())
{
    //echo "P " . $row["coin"] . "<BR>";
    $pr = (($row["buyAmount"] / $row["buyPrice"]) * $row["sellPrice"]) - ($row["buyAmount"]);
	$profit += $pr;
	if ($pr >= 0) {$profitTrades++;} else {$unprofitTrades++;};
}
$profitFiat = round($profit * $btcPrice);
echo '<div class="stat">Yesterday: ';
if ($unprofitTrades > 0) {echo '<span style="color: Red;">' . $unprofitTrades . '</span> ';}
echo '<span style="color: Green;">' . $profitTrades . '</span> ' . rtrim(number_format($profit,5), '0') . ' $' . $profitFiat . '</div>';
'</div>';

//This week
$profit = $profitTrades = $unprofitTrades = 0;
$results = $db->query('SELECT * FROM coins WHERE sellDate IS NOT NULL AND YEARWEEK(from_unixtime(sellDate), 1) =  YEARWEEK(CURRENT_DATE, 1)');
while ($row = $results->fetch_assoc())
{
    $pr = (($row["buyAmount"] / $row["buyPrice"]) * $row["sellPrice"]) - ($row["buyAmount"]);
	$profit += $pr;
	if ($pr >= 0) {$profitTrades++;} else {$unprofitTrades++;};
}
$results = $db->query('SELECT * FROM program WHERE sellDate IS NOT NULL AND YEARWEEK(from_unixtime(sellDate), 1) =  YEARWEEK(CURRENT_DATE, 1)');
while ($row = $results->fetch_assoc())
{
    //echo "P " . $row["coin"] . "<BR>";
    $pr = (($row["buyAmount"] / $row["buyPrice"]) * $row["sellPrice"]) - ($row["buyAmount"]);
	$profit += $pr;
	if ($pr >= 0) {$profitTrades++;} else {$unprofitTrades++;};
}
$profitFiat = round($profit * $btcPrice);
echo '<div class="stat">This week: ';
if ($unprofitTrades > 0) {echo '<span style="color: Red;">' . $unprofitTrades . '</span> ';}
echo '<span style="color: Green;">' . $profitTrades . '</span> ' . rtrim(number_format($profit,5), '0') . ' $' . $profitFiat . '</div>';
'</div>';

//This month
$profit = $profitTrades = $unprofitTrades = 0;
$results = $db->query('SELECT * FROM coins WHERE sellDate IS NOT NULL AND YEAR(from_unixtime(sellDate)) = YEAR(CURRENT_DATE) AND MONTH(from_unixtime(sellDate)) = MONTH(CURRENT_DATE)');
while ($row = $results->fetch_assoc())
{
    $pr = (($row["buyAmount"] / $row["buyPrice"]) * $row["sellPrice"]) - ($row["buyAmount"]);
	$profit += $pr;
	if ($pr >= 0) {$profitTrades++;} else {$unprofitTrades++;};
}
$results = $db->query('SELECT * FROM program WHERE sellDate IS NOT NULL AND YEAR(from_unixtime(sellDate)) = YEAR(CURRENT_DATE) AND MONTH(from_unixtime(sellDate)) = MONTH(CURRENT_DATE)');
while ($row = $results->fetch_assoc())
{
    //echo "P " . $row["coin"] . "<BR>";
    $pr = (($row["buyAmount"] / $row["buyPrice"]) * $row["sellPrice"]) - ($row["buyAmount"]);
	$profit += $pr;
	if ($pr >= 0) {$profitTrades++;} else {$unprofitTrades++;};
}
$profitFiat = round($profit * $btcPrice);
echo '<div class="stat">This month: ';
if ($unprofitTrades > 0) {echo '<span style="color: Red;">' . $unprofitTrades . '</span> ';}
echo '<span style="color: Green;">' . $profitTrades . '</span> ' . rtrim(number_format($profit,5), '0') . ' $' . $profitFiat . '</div>';
'</div>';

//Total
$profit = $profitTrades = $unprofitTrades = 0;
$results = $db->query('SELECT * FROM coins WHERE sellDate IS NOT NULL');
while ($row = $results->fetch_assoc())
{
    $pr = (($row["buyAmount"] / $row["buyPrice"]) * $row["sellPrice"]) - ($row["buyAmount"]);
	$profit += $pr;
	if ($pr >= 0) {$profitTrades++;} else {$unprofitTrades++;};
}
$results = $db->query('SELECT * FROM program WHERE sellDate IS NOT NULL');
while ($row = $results->fetch_assoc())
{
    //echo "P " . $row["coin"] . "<BR>";
    $pr = (($row["buyAmount"] / $row["buyPrice"]) * $row["sellPrice"]) - ($row["buyAmount"]);
	$profit += $pr;
	if ($pr >= 0) {$profitTrades++;} else {$unprofitTrades++;};
}
$profitFiat = round($profit * $btcPrice);
echo '<BR><div class="stat">Total: ';
if ($unprofitTrades > 0) {echo '<span style="color: Red;">' . $unprofitTrades . '</span> ';}
echo '<span style="color: Green;">' . $profitTrades . '</span> ' . rtrim(number_format($profit,5), '0') . ' $' . $profitFiat . '</div>';
'</div>';
exit();























//24 hour stats
$currentTime = time();
$maxSellDate = $currentTime - 86400;
$tfhProfit = 0;
$queryArray = array();
//Fetch results from table "coins"
$results = $db->query('SELECT * FROM coins WHERE sellDate IS NOT NULL AND sellDate > ' . $maxSellDate . ' ORDER BY sellDate ASC');
while ($row = $results->fetch_assoc())
{
    $queryArray[] = array("category" => "A",
						  "coin" => $row["marketName"],
						  "buyAmount" => $row["buyAmount"],
						  "buyPrice" => $row["buyPrice"],
						  "buyDate" => $row["buyDate"],
						  "sellPrice" => $row["sellPrice"],
						  "sellDate" => $row["sellDate"],
						  "comment" => $row["comment"],
						  );
}
//Fetch results from table "program"
$results = $db->query('SELECT * FROM program WHERE sellDate IS NOT NULL AND sellDate > ' . $maxSellDate . ' ORDER BY sellDate ASC');
while ($row = $results->fetch_assoc())
{
    $queryArray[] = array("category" => "P",
						  "coin" => $row["coin"],
						  "buyAmount" => $row["buyAmount"],
						  "buyPrice" => $row["buyPrice"],
						  "buyDate" => $row["buyDate"],
						  "sellPrice" => $row["sellPrice"],
						  "sellDate" => $row["sellDate"],
						  "comment" => $row["comment"],
						  );
}
//Sort the array by sell date
uasort($queryArray, function($a, $b) {return $a['sellDate'] - $b['sellDate'];});

echo '<div class="header">24h trades</div>';
foreach ($queryArray as $dbMarket)
{
	$currentProfit = (($dbMarket["buyAmount"] / $dbMarket["buyPrice"]) * $dbMarket["sellPrice"]) - ($dbMarket["buyAmount"]);
	$tfhProfit += $currentProfit;
	
	$change = round(($dbMarket["sellPrice"] - $dbMarket["buyPrice"]) / ($dbMarket["buyPrice"] / 100), 2);
	if ($change < 0) {$color = "Red";} else {$color = "Green";}
	
	echo "<div class=\"sold\">";
	echo "<span class=\"sellDate\">" . date('M d H:i', $dbMarket["sellDate"]) . "</span>";
	echo "<span class=\"category\"> " . $dbMarket["category"] . "</span>";
	echo " <a class=tooltip href=https://bittrex.com/Market/Index?MarketName=". $dbMarket["coin"] . " target=_blank>" .$dbMarket["coin"] . "<span>";
	echo holdingTime($dbMarket["buyDate"]);
	echo "  " . $dbMarket["comment"] .  "</span></a>  <span style=\"color:" . $color . "\">" . $change . "%</span>";
	echo " Bought at: " . rtrim(number_format($dbMarket["buyPrice"],10), '0');
	//echo  " " . date('m-d H:i', $dbMarket["buyDate"]);
	echo " Sold at: " . rtrim(number_format($dbMarket["sellPrice"],10), '0');
	echo " Profit: <span style=\"color:" . $color . "\">" . rtrim(number_format($currentProfit,5), '0') . "</span>";
	echo "</div>";
}

$tfhProfitFiat = round($tfhProfit * $btcPrice);

echo '<div class="header">Profit: ' . rtrim(number_format($tfhProfit,5), '0') . ' $' . $tfhProfitFiat . '</div>';
?>

</div>
<BR><BR><BR><BR>
</body>
</html>