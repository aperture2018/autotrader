<?PHP
class Log {
    public $origin = '';
	public $type = '';
	public $message = '';
	
    public function write() {
	    global $db;
		$date = date("j M Y, G:i:s");
		//Put the record in database
		$statement = $db->prepare('INSERT INTO log (date, origin, type, message) VALUES (?, ?, ?, ?)');
		$statement->bind_param('ssss', $date, $this->origin, $this->type, $this->message);
		$statement->execute();
		$statement->close();
    }
}
?>
