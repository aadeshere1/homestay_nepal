<?php 

/** 
* class to ahngle all db operations
* this class will have crud methods for database tables
*
* @author Aadesh Shrestha
*/

class DbHandler{

	private $conn;

	function __construct(){
		require_once dirname(__FILE__). '/DbConnect.php';
		//opening db connection

		$db = new DbConnect();
		$this->conn = $db->connect();
	}
	/*----------user create method-----*/
    public function createUser($name, $email, $phone, $password, $who){
        require_once 'PassHash.php';
        $response = array();

        if ($who == 'owner'){
            $status = "owner";
            
        } elseif ($who == 'tourist'){
            $status = "tourist";
        } else {
            return NULL;
        }

        if (!$this->isOwnerExists($email, $phone)){
                //passhash
                $password_hash = PassHash::hash($password);
                //generate api
                $api_key = $this->generateApiKey();

                //insert query
                $stmt = $this->conn->prepare("INSERT INTO owner(name, email, phone, password_hash, api_key, who) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssss", $name, $email, $phone, $password_hash, $api_key, $status);
                $result = $stmt->execute();
                $stmt->close();
                // check for successful insertion
                if ($result){
                    // owner successfully inserted
                    return USER_CREATED_SUCCESSFULLY;
                } else {
                    // failed to create
                    return USER_CREATE_FAILED;
                }
        } else {
            return USER_ALREADY_EXISTED;
        }
        return $response;
    }

	
	/*----------`tourist` table method--------*/
	/**
	* Creating new tourist
	* @param String $name tourist Full name
	* @param String $email tourist Email
	* @param String $phone tourist Phone
	* @param String $password tourist Password
	*/
	public function createTourist($name, $email, $phone, $password){
		require_once 'PassHash.php';
		$response = array();

		//First check if owner already existed in db
		if (!$this->isTouristExists($email, $phone)){
			//Generating password hash
			$password_hash = PassHash::hash($password);

			//Generating API key
			$api_key = $this->generateApiKey();

			//insert query
			$stmt = $this->conn->prepare("INSERT INTO tourist(name, email,phone,password_hash, api_key, status) values(?, ?, ?, ?, ?, 1)");
			$stmt->bind_param("sssss", $name, $email, $phone, $password_hash, $api_key);

			$result = $stmt->execute();

			$stmt->close();

			// check for successful insertion
			if ($result){
				// owner successfully inserted
				return USER_CREATED_SUCCESSFULLY;
			} else {
				// failed to create
				return USER_CREATE_FAILED;
			}
		} else {
			// owner already exists
			return USER_ALREADY_EXISTED;
		}

		return $response;
	}
	/**
	* Checking for duplicate tourist by email and phone
	* @return boolean
	*/
	private function isTouristExists($email, $phone){
		$stmt = $this->conn->prepare("SELECT id from tourist WHERE email = ? OR phone = ?");
		$stmt->bind_param("ss", $email, $phone);
		$stmt->execute();
		$stmt->store_result();
		$num_rows = $stmt->num_rows;
		$stmt->close();
		return $num_rows > 0;
	}

	/**
	* Checking owner login
	* @param String $email owner login email id
	* @param String $password owner login password
	* @return boolean owner login status success/fail
	*/

	public function checkTouristLogin($email, $password){
		//fetcing owner by email
		$stmt = $this->conn->prepare("SELECT password_hash FROM tourist WHERE email=?");
		$stmt->bind_param("s",$email);
		$stmt->execute();
		$stmt->bind_result($password_hash);
		$stmt->store_result();

		if($stmt->num_rows > 0){
			//found owner with the email
			//now verify the password

			$stmt->fetch();
			$stmt->close();

			if (PassHash::check_password($password_hash, $password)){
				// password is correct
				return TRUE;
			} else {
				// owner password is incorrect
				return FALSE;
			}
		} else {
			$stmt->close();

			// owner not existed with the email
			return FALSE;
		}
	}

	/**
	* Fetching owner by email
	* @param String $email owner email id
	*/
	public function getTouristByEmail($email){
		$stmt = $this->conn->prepare("SELECT name, email, phone, api_key, status, created_at FROM tourist WHERE email = ?");
		$stmt->bind_param("s", $email);
		if ($stmt->execute()){
			$owner = $stmt->get_result()->fetch_assoc();
			$stmt->close();
			return $owner;
		} else {
			return NULL;
		}
	}

	/**
	* validating owner api key
	* @param string $api_key tourist api key
	* @return boolean
	*/
	public function isValidTouristApiKey($api_key){
		$stmt = $this->conn->prepare("SELECT id FROM tourist WHERE api_key = ?");
		
		$stmt->bind_param("s", $api_key);
		$stmt->execute();
		$stmt->store_result();
		$num_rows = $stmt->num_rows;
		$stmt->close();
		return $num_rows > 0;
	}
	/**
	* Fetching owner id by api key
	* @param String $api_key owner api key
	*/
	public function getTouristId($api_key){
		$stmt = $this->conn->prepare("SELECT id FROM tourist WHERE api_key =?");
		$stmt->bind_param("s", $api_key);
		if ($stmt->execute()){
			$owner_id = $stmt->get_result()->fetch_assoc();
			$stmt->close();
			return $owner_id;
		} else {
			return NULL;
		}
	}
	/*----------`owner` table method----------*/
	/**
	* Creating new owner
	* @param String $name Owner Full name
	* @param String $email Owner email address
	* @param String $password Owner login password
	* @param String $phone Owner phone number
	*/

	public function createOwner($name, $email, $phone, $password){
		require_once 'PassHash.php';
		$response = array();

		//First check if owner already existed in db
		if (!$this->isOwnerExists($email, $phone)){
			//Generating password hash
			$password_hash = PassHash::hash($password);

			//Generating API key
			$api_key = $this->generateApiKey();

			//insert query
			$stmt = $this->conn->prepare("INSERT INTO owner(name, email,phone,password_hash, api_key, who) values(?, ?, ?, ?, ?, 1)");
			$stmt->bind_param("sssss", $name, $email, $phone, $password_hash, $api_key);

			$result = $stmt->execute();

			$stmt->close();

			// check for successful insertion
			if ($result){
				// owner successfully inserted
				return USER_CREATED_SUCCESSFULLY;
			} else {
				// failed to create
				return USER_CREATE_FAILED;
			}
		} else {
			// owner already exists
			return USER_ALREADY_EXISTED;
		}

		return $response;
	}

	/**
	* Checking owner login
	* @param String $email owner login email id
	* @param String $password owner login password
	* @return boolean owner login status success/fail
	*/

	public function checkOwnerLogin($email, $password){
		//fetcing owner by email
		$stmt = $this->conn->prepare("SELECT password_hash FROM owner WHERE email=?");
		$stmt->bind_param("s",$email);
		$stmt->execute();
		$stmt->bind_result($password_hash);
		$stmt->store_result();

		if($stmt->num_rows > 0){
			//found owner with the email
			//now verify the password

			$stmt->fetch();
			$stmt->close();

			if (PassHash::check_password($password_hash, $password)){
				// password is correct
				return TRUE;
			} else {
				// owner password is incorrect
				return FALSE;
			}
		} else {
			$stmt->close();

			// owner not existed with the email
			return FALSE;
		}
	}

	/**
	* Checking for duplicate owner by email and phone
	* @return boolean
	*/
	private function isOwnerExists($email, $phone){
		$stmt = $this->conn->prepare("SELECT id from owner WHERE email = ? OR phone = ?");
		$stmt->bind_param("ss", $email, $phone);
		$stmt->execute();
		$stmt->store_result();
		$num_rows = $stmt->num_rows;
		$stmt->close();
		return $num_rows > 0;
	}

	/**
	* Fetching owner by email
	* @param String $email owner email id
	*/
	public function getOwnerByEmail($email){
		$stmt = $this->conn->prepare("SELECT name, email, phone, api_key, who, created_at FROM owner WHERE email = ?");
		$stmt->bind_param("s", $email);
		if ($stmt->execute()){
			$owner = $stmt->get_result()->fetch_assoc();
			$stmt->close();
			return $owner;
		} else {
			return NULL;
		}
	}
	/**
	* Fetching owner by id
	* @param String $email owner email id
	*/
	public function getOwnerById($id){
		$stmt = $this->conn->prepare("SELECT name, email, phone, api_key, who, created_at FROM owner WHERE id = ?");
		$stmt->bind_param("i", $id);
		if ($stmt->execute()){
			$owner = $stmt->get_result()->fetch_assoc();
			$stmt->close();
			return $owner;
		} else {
			return NULL;
		}
	}

	/** 
	* Fetching owner api key
	* @param String $owner_id owner id primary key in owner table
	*/
	public function getApiKeyById($owner_id){
		$stmt = $this->conn->prepare("SELECT api_key FROM owner WHERE id = ?");
		$stmt->bind_param("i", $owner_id);
		if($stmt->execute()){
			$api_key = $stmt->get_result()->fetch_assoc();
			$stmt->close();
			return $api_key;
		} else {
			return NULL;
		}
	}

	/**
	* Fetching owner id by api key
	* @param String $api_key owner api key
	*/
	public function getOwnerId($api_key){
		$stmt = $this->conn->prepare("SELECT id FROM owner WHERE api_key =?");
		$stmt->bind_param("s", $api_key);
		if ($stmt->execute()){
			$owner_id = $stmt->get_result()->fetch_assoc();
			$stmt->close();
			return $owner_id;
		} else {
			return NULL;
		}
	}

	/**
	* validating owner api key
	* @param string $api_key owner api key
	* @return boolean
	*/

	public function isValidOwnerApiKey($api_key){
		$stmt = $this->conn->prepare("SELECT id FROM owner WHERE api_key = ?");
		
		$stmt->bind_param("s", $api_key);
		$stmt->execute();
		$stmt->store_result();
		$num_rows = $stmt->num_rows;
		$stmt->close();
		return $num_rows > 0;
	}

	/**
	* Generating random unique MD5 string for user api key
	*/
	private function generateApiKey(){
		return md5(uniqid(rand(), true));
	}

	


	
	/**--------------`rooms`--------------**/
	/**
	* Entering rooms amneties in the database
	* params - $name, $price, $address, $numRooms, $smoking, $alcohol, $kitchen, $internet, $breakfast, $description
	*/
	public function isRoomUploaded($owner_id){
		$stmt = $this->conn->prepare("SELECT id FROM owner_rooms WHERE owner_id = ?");
		$stmt->bind_param("i", $owner_id);
		$stmt->execute();
		$stmt->store_result();
		$result = $stmt->num_rows;
		$stmt->close();
		return $result > 0;
	}

	public function createRoom($owner_id, $name, $price, $address, $num_rooms, $smoking, $alcohol, $kitchen, $internet, $breakfast, $description) {        
        $stmt = $this->conn->prepare("INSERT INTO rooms(homestay_name, price, address, num_rooms, smoking, alcohol, kitchen, internet, breakfast, description) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");        
        $stmt->bind_param("sisissssss",  $name, $price, $address, $num_rooms, $smoking, $alcohol, $kitchen, $internet, $breakfast, $description);
        $result = $stmt->execute();
        
 
        if ($result) {
            // task row createdf
            // now assign the task to user            
            $newRoomId = $this->conn->insert_id;
            
            //the following extract will return the value of id from the array.
            extract($owner_id,EXTR_PREFIX_SAME, "wddx");
            $stmt->close();
            $res = $this->createOwnerRoom($id, $newRoomId);

            if ($res) {
                // task created successfully
                return $newRoomId;
            } else {
                // task failed to create
                return NULL;
            }
        } else {
            // task failed to create
            return NULL;
        }
        
    }

/**
 * Fetching single task
 * @param String $room_id of the room
 */
    // public function getRoom($room_id, $owner_id){
    	// $stmt = $this->conn->prepare("SELECT r.id, r.homestay_name, r.price, r.address, r.num_rooms, r.smoking, r.alcohol, r.kitchen, r.internet, r.breakfast, r.description, r.created_at FROM rooms r, owner_rooms orR WHERE r.id = ? AND orR.room_id = r.id AND orR.owner_id = ?");

    // 	extract($owner_id,EXTR_PREFIX_SAME, "wddx");
    // 	$owner_id = $id;
    // 	$stmt->bind_param("ii", $room_id, $owner_id);
    	// if ($stmt->execute()){
    // 		$room = $stmt->get_result()->fetch_assoc();
    // 		$stmt->close();
    // 		return $room;
    // 	} else {
    // 		return NULL;
    // 	}
    // }

/**
 * Fetching all rooms
 * @param String $owner_id or $tourist_id
 */
	public function getAllRooms(){
		$stmt = $this->conn->prepare("SELECT * FROM rooms ORDER BY created_at DESC");
		$stmt->execute();
		$rooms = $stmt->get_result();
		$stmt->close();
		return $rooms;
	}
	
	public function getOwnerIdByRoomId($id){
		$stmt = $this->conn->prepare("SELECT owner_id FROM owner_rooms WHERE room_id =?");
		$stmt->bind_param("i", $id);
		$stmt->execute();
		$result = $stmt->get_result()->fetch_assoc();
		$stmt->close();
		return $result;
		// if ($stmt->execute()){
		// 	$owner_id = $stmt->get_result()->fetch_assoc();
		// 	$stmt->close();
		// 	return $owner_id;
		// } else {
		// 	return NULL;
		// }
		
	}

	public function search($keyword){
		$stmt = $this->conn->prepare("SELECT * FROM rooms WHERE homestay_name LIKE ?");
		$sql = '%'.$keyword.'%';
		$stmt->bind_param("s", $sql);
		$stmt->execute();
		$result = $stmt->get_result();
		$stmt->close();
		return $result;
	}

	public function updateRoom($owner_id, $room_id, $homestay_name, $price, $address, $num_rooms, $smoking, $alcohol, $kitchen, $internet, $breakfast, $description){
		$stmt = $this->conn->prepare("UPDATE rooms r, owner_rooms orR set r.homestay_name = ?, r.price = ?, r.address = ?, r.num_rooms = ?, r.smoking = ?, r.alcohol = ?, r.kitchen = ?, r.internet = ?, r.breakfast = ?, r.description = ? 
			WHERE 
			r.id = ? AND r.id = orR.room_id AND orR.owner_id = ?");
		// extract($owner_id,EXTR_PREFIX_SAME, "wddx");
		$stmt->bind_param("sisissssssii",$homestay_name, $price, $address, $num_rooms, $smoking, $alcohol, $kitchen, $internet, $breakfast, $description, $room_id, $owner_id);
		$stmt->execute();
		$num_affected_rows = $stmt->affected_rows;
		$stmt->close();
		return $num_affected_rows > 0;
	}

	/**
	 * Deleting a room
	 *
	 */
	public function deleteRoom($owner_id, $room_id){
		$stmt = $this->conn->prepare("DELETE r FROM rooms r, owner_rooms orR WHERE r.id =? AND orR.room_id = r.id AND orR.owner_id = ?");
		$stmt ->bind_param("ii", $room_id, $owner_id);
		$stmt->execute();
		$num_affected_rows = $stmt->affected_rows;
		$stmt->close();
		return $num_affected_rows > 0;
	}


    /* ------------- `user_tasks` table method ------------------ */
 
    /**
     * Function to assign a task to user
     * @param String $owner_id id of the user
     * @param String $task_id id of the task
     */
    public function createOwnerRoom($owner_id, $newRoomId){
		$stmt = $this->conn->prepare("INSERT INTO owner_rooms(owner_id, room_id) VALUES(?,?)");
		$stmt->bind_param("ii",$owner_id,$newRoomId);
		$result = $stmt->execute();
		$stmt->close();
		return $result;
	}

/* Class DbHandler exit here */
}

// $new = new DbHandler();
// // $res = $new->updateRoom(14,190,"haha", 500, "gath", 5, "n","n","n","n","n","n");
// $res = $new->getOwnerIdByRoomId(174);
// print_r($res);
// echo "hahahahaha";
// $res = $new->getOwnerById(53);
// print_r($res);

?>