<?PHP
require 'config.inc.php';
set_time_limit(0);
$startTime = $currentTime = time();
$endTime = $startTime + 840; //14 minutes

// Get current mode
$results = $db->query('SELECT * FROM settings');
while ($row = $results->fetch_assoc()) {$settings[$row["Setting"]] = $row["Value"];}
$mode = $settings["mode"];

// Loop the script for about 15 minutes with $updateFrequency intervals or until "stopall" mode is set in db
while ($currentTime < $endTime && $mode != "stopall")
{
	// Get settings
	$results = $db->query('SELECT * FROM settings');
	while ($row = $results->fetch_assoc()) {
	    $settings[$row["Setting"]] = $row["Value"];
	}
	$mode = $settings["mode"];
	$updateFrequency = $settings["updateFrequency"];
	
	// Get market summaries and write to db
	Bittrex::refreshDatabase($currentTime);
	
	// Update program coins
    ProgramMonitor::update();
	
	// Auto sell
	ProgramMonitor::autoSell();
    
	// Update account balance setting
    Bittrex::getBalance();
	
	// Sleep for $updateFrequency seconds
	sleep($updateFrequency);
	
	// Check current time
	$currentTime = time();
}

// Debug purposes only
$retime = time();
$execTime = ($retime - $startTime)/60;
$log = new Log;
$log->type = 'Info';
$log->origin = 'Monitor';
$log->message = 'Exec time: ' . round($execTime, 2);
$log->write();
?>