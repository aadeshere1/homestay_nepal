
<?php
require_once '../include/DbHandler.php';
require_once '../include/PassHash.php';
require '../libs/Slim/Slim.php';

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

// User id from db - Global Variable
$owner_id = NULL;
$tourist_id = NULL;
/**
 * Adding Middle Layer to authenticate every request
 * Checking if the request has valid api key in the 'Authorization' header
 */
function authenticate(\Slim\Route $route) {
    // Getting request headers
    $headers = apache_request_headers();
    $response = array();
    $app = \Slim\Slim::getInstance();

    // Verifying Authorization Header
    if (isset($headers['authorization'])) {
        $db = new DbHandler();

        // get the api key
        $api_key = $headers['authorization'];
        // validating api key
        if ($db->isValidOwnerApiKey($api_key)) {
            global $owner_id;

            $owner_id = $db->getOwnerId($api_key);
            
        } elseif($db->isValidTouristApiKey($api_key)) {
            global $tourist_id;
            // get user primary key id
            $tourist_id = $db->getTouristId($api_key);
        } else {
            // api key is not present in users table
            $response["error"] = true;
            $response["message"] = "Access Denied. Invalid Api key";
            echoRespnse(401, $response);
            $app->stop();
        }
    } else {
        // api key is missing in header
        $response["error"] = true;
        $response["message"] = "Api key is misssing";
        echoRespnse(400, $response);
        $app->stop();
    }
}
// function authenticate(\Slim\Route $route) {
//     // Getting request headers
//     $headers = apache_request_headers();
//     $response = array();
//     $app = \Slim\Slim::getInstance();

//     // Verifying Authorization Header
//     if (isset($headers['Authorization'])) {
//         $db = new DbHandler();

//         // get the api key
//         $api_key = $headers['Authorization'];
//         // validating api key
//         if (!$db->isValidApiKey($api_key)) {
//             // api key is not present in users table
//             $response["error"] = true;
//             $response["message"] = "Access Denied. Invalid Api key";
//             echoRespnse(401, $response);
//             $app->stop();
//         } else {
//             global $owner_id;
//             // get user primary key id
//             $owner_id = $db->getOwnerId($api_key);
//         }
//     } else {
//         // api key is missing in header
//         $response["error"] = true;
//         $response["message"] = "Api key is misssing";
//         echoRespnse(400, $response);
//         $app->stop();
//     }
// }

/**
 * ----------- METHODS WITHOUT AUTHENTICATION ---------------------------------
 */
/**
 * User Registration
 * url - /register
 * method - POST
 * params - name, email, password
 */
$app->post('/register', function() use ($app){
        //check for req params
        verifyRequiredParams(array('name', 'email', 'phone', 'password', 'who'));
        $response = array();
        //reading post params
        $name = $app->request->post('name');
        $email = $app->request->post('email');
        $phone = $app->request->post('phone');
        $password = $app->request->post('password');
        $who = $app->request->post('who');
        //validate email
        validateEmail($email);
        $db = new DbHandler();
        $res = $db->createUser($name, $email, $phone, $password, $who);
        if ($res == USER_CREATED_SUCCESSFULLY){
            $user = $db->getOwnerByEmail($email);
            if ($user != NULL){
                $response["error"] = false;
                $response["message"] = "You are successfully registered";
                $response['name'] = $user['name'];
                $response['email'] = $user['email'];
                $response['phone'] = $user['phone'];
                $response['status'] = $user['who'];
                $response['apiKey'] = $user['api_key'];
                $response['createdAt'] = $user['created_at'];
            } else if ($res == USER_CREATE_FAILED) {
                $response["error"] = true;
                $response["message"] = "Oops! An error occurred while registereing";
            } else if ($res == USER_ALREADY_EXISTED) {
                $response["error"] = true;
                $response["message"] = "Sorry, this user already existed";
            }
            // echo json response
            echoRespnse(201, $response);
        } 
});
 
$app->post('/register/tourist', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('name', 'email', 'phone', 'password'));
            $response = array();
            // reading post params
            $name = $app->request->post('name');
            $email = $app->request->post('email');
            $phone = $app->request->post('phone');
            $password = $app->request->post('password');
            // validating email address
            validateEmail($email);

            $db = new DbHandler();
            $res = $db->createTourist($name, $email, $phone, $password);

            if ($res == USER_CREATED_SUCCESSFULLY) {
                $user = $db->getTouristByEmail($email);
                
                if($user != NULL){
                    $response["error"] = false;
                    $response["message"] = "You are successfully registered";
                    $response['name'] = $user['name'];
                    $response['email'] = $user['email'];
                    $response['phone'] = $user['phone'];
                    $response['apiKey'] = $user['api_key'];
                    $response['createdAt'] = $user['created_at'];
                } else {
                    $response['error'] = true;
                    $response['message'] = "An error occurred. Please try again";
                }
                
                
            } else if ($res == USER_CREATE_FAILED) {
                $response["error"] = true;
                $response["message"] = "Oops! An error occurred while registereing";
            } else if ($res == USER_ALREADY_EXISTED) {
                $response["error"] = true;
                $response["message"] = "Sorry, this user already existed";
            }
            // echo json response
            echoRespnse(201, $response);
        });

/**
 * User Registration
 * url - /register
 * method - POST
 * params - name, email, password
 */
$app->post('/register/owner', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('name', 'email', 'phone', 'password'));

            $response = array();

            // reading post params
            $name = $app->request->post('name');
            $email = $app->request->post('email');
            $phone = $app->request->post('phone');
            $password = $app->request->post('password');

            // validating email address
            validateEmail($email);

            $db = new DbHandler();
            $res = $db->createOwner($name, $email, $phone, $password);

            if ($res == USER_CREATED_SUCCESSFULLY) {
                $user = $db->getOwnerByEmail($email);
                if($user != NULL){
                    $response["error"] = false;
                    $response["message"] = "You are successfully registered.";
                    $response["name"] = $user['name'];
                    $response["email"] = $user['email'];
                    $response["phone"] = $user['phone'];
                    $response["apiKey"] = $user['api_key'];
                    $response["createdAt"] = $user['created_at'];
                } else {
                    $response['error'] = true;
                    $response['message'] = "An error occurred. Please try again";
                }
                // $response["error"] = false;
                // $response["message"] = "You are successfully registered";
                
            } else if ($res == USER_CREATE_FAILED) {
                $response["error"] = true;
                $response["message"] = "Oops! An error occurred while registereing";
            } else if ($res == USER_ALREADY_EXISTED) {
                $response["error"] = true;
                $response["message"] = "Sorry, this user already existed";
            }
            // echo json response
            header('Content-type: application/json');
            echoRespnse(201, $response);
        });

/**
 * Owner Login
 * url - /login/owner
 * method - POST
 * params - email, password
 */
$app->post('/login/owner', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('email', 'password'));

            // reading post params
            $email = $app->request()->post('email');
            $password = $app->request()->post('password');
            $response = array();

            $db = new DbHandler();
            // check for correct email and password
            if ($db->checkOwnerLogin($email, $password)) {
                // get the user by email
                $user = $db->getOwnerByEmail($email);

                if ($user != NULL) {
                  
                    
                    $response["error"] = false;
                    $response['name'] = $user['name'];
                    $response['email'] = $user['email'];
                    $response['apiKey'] = $user['api_key'];
                    $response['phone'] = $user['phone'];
                    $response['who'] = $user['who'];
                    $response['createdAt'] = $user['created_at'];
                } else {
                    // unknown error occurred
                    $response['error'] = true;
                    $response['message'] = "An error occurred. Please try again";
                }
            } else {
                // user credentials are wrong
                $response['error'] = true;
                $response['message'] = 'Login failed. Incorrect credentials';
            }
            
            echoRespnse(200, $response);
        });

/**
 * tourist Login
 * url - /login/tourist
 * method - POST
 * params - email, password
 */
$app->post('/login/tourist', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('email', 'password'));

            // reading post params
            $email = $app->request()->post('email');
            $password = $app->request()->post('password');
            $response = array();

            $db = new DbHandler();
            // check for correct email and password
            if ($db->checkTouristLogin($email, $password)) {
                // get the user by email
                $user = $db->getTouristByEmail($email);

                if ($user != NULL) {
                    $response["error"] = false;
                    $response['name'] = $user['name'];
                    $response['email'] = $user['email'];
                    $response['apiKey'] = $user['api_key'];
                    $response['createdAt'] = $user['created_at'];
                } else {
                    // unknown error occurred
                    $response['error'] = true;
                    $response['message'] = "An error occurred. Please try again";
                }
            } else {
                // user credentials are wrong
                $response['error'] = true;
                $response['message'] = 'Login failed. Incorrect credentials';
            }

            echoRespnse(200, $response);
        });

 /** ----------- METHODS REQUIRING AUTHENTICATION --------------------------------- */

/**
 * Creating new room in db
 * method - POST
 * params - name
 * url - /rooms/
 */

$app->post('/rooms', 'authenticate', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('homestay', 'price', 'address','num_rooms', 'smoking', 'alcohol', 'kitchen', 'internet', 'breakfast', 'description'));
            
            $response = array();
            $homestay = $app->request->post('homestay');
            $price = $app->request->post('price');
            $address = $app->request->post('address');
            $num_rooms = $app->request->post('num_rooms');
            $smoking = $app->request->post('smoking');
            $alcohol = $app->request->post('alcohol');
            $kitchen = $app->request->post('kitchen');
            $internet = $app->request->post('internet');
            $breakfast = $app->request->post('breakfast');
            $description = $app->request->post('description');
 
            global $owner_id;
            $temp = $owner_id;
            $db = new DbHandler();
            // creating new task
            $bool = $db->isRoomUploaded($temp);

            if ($bool){
                $response["error"] = true;
                $response["message"] = "One room by this user already exists.";
            } else {
                $task_id = $db->createRoom($temp, $homestay, $price, $address, $num_rooms, $smoking, $alcohol, $kitchen, $internet, $breakfast, $description);
            
                if ($task_id != NULL) {
                    $response["error"] = false;
                    $response["message"] = "Task created successfully";
                    $response["task_id"] = $task_id;
                } else {
                    $response["error"] = true;
                    $response["message"] = "Failed to create task. Please try again";
                }
            }
            
            echoRespnse(201, $response);
        });
/**
 * Listing all rooms
 * method GET
 * url /explore
 */
$app->get('/explore', 'authenticate', function() use ($app){

    $response = array();
    $db = new DbHandler();

    //fetching all rooms
    $result = $db->getAllRooms();
    $response["error"] = false;
    $response["rooms"] = array();

    //looping through result and preparing rooms array
    while ($room = $result->fetch_assoc()){
        $tmp = array();
        $tmp["id"] = $room["id"];
        $temp = $room['id'];
        
        $resl = $db->getOwnerIdByRoomId($temp);
        
        extract($resl,EXTR_PREFIX_ALL, "new");
        
        //echo(extract($resl, EXTR_PREFIX_SAME, "wddx"));
        $res = $db->getOwnerById($new_owner_id);
        if($res != NULL){
            $tmp['owner_name'] = $res['name'];
            $tmp['owner_phone'] = $res['phone'];
            $tmp['owner_email'] = $res['email'];
        } else {
            $tmp['message'] = "error";
        }
        
        $tmp["homestay"] = $room["homestay_name"];
        $tmp["price"] = $room["price"];
        $tmp["addaress"] = $room["address"];
        $tmp["num_rooms"] = $room["num_rooms"];
        $tmp["smoking"] = $room["smoking"];
        $tmp["alcohol"] = $room["alcohol"];
        $tmp["kitchen"] = $room["kitchen"];
        $tmp["internet"] = $room["internet"];
        $tmp["breakfast"] = $room["breakfast"];
        $tmp["description"] = $room["description"];
        array_push($response["rooms"], $tmp);
    }
    echoRespnse(800, $response);
});


$app->get('/search/:keyword', 'authenticate', function($key){
    //verifyRequiredParams(array('keyword'))
    $response = array();
    
    $db = new DbHandler();

    $result = $db->search($key);
    $response["error"] = false;
    $response["rooms"] = array();

    if ($result != NULL){
         //looping through search result
        while ($room = $result->fetch_assoc()){
            $tmp = array();
            $tmp["id"] = $room["id"];
            $tmp["homestay"] = $room["homestay_name"];
            $tmp["price"] = $room["price"];
            $tmp["addaress"] = $room["address"];
            $tmp["num_rooms"] = $room["num_rooms"];
            $tmp["smoking"] = $room["smoking"];
            $tmp["alcohol"] = $room["alcohol"];
            $tmp["kitchen"] = $room["kitchen"];
            $tmp["internet"] = $room["internet"];
            $tmp["breakfast"] = $room["breakfast"];
            $tmp["description"] = $room["description"];
            array_push($response["rooms"], $tmp);
        }
    } else {
        $response["error"] = true;
        $response["message"] = "The requested resource doesn't exists";
        echoRespnse(404, $response);
    }
   
    echoRespnse(200, $response);
});

/**
 * Ugdating existing room desc
 * method PUT
 * params 
 * url - /rooms/:id
 */
$app->put('/rooms/:id', 'authenticate', function($room_id) use ($app){
    //check for required params
    verifyRequiredParams(array('homestay', 'price', 'address','num_rooms', 'smoking', 'alcohol', 'kitchen', 'internet', 'breakfast', 'description'));

    global $owner_id;
    $response = array();
    $homestay = $app->request->put('homestay');
    $price = $app->request->put('price');
    $address = $app->request->put('address');
    $num_rooms = $app->request->put('num_rooms');
    $smoking = $app->request->put('smoking');
    $alcohol = $app->request->put('alcohol');
    $kitchen = $app->request->put('kitchen');
    $internet = $app->request->put('internet');
    $breakfast = $app->request->put('breakfast');
    $description = $app->request->put('description');
    
    extract($owner_id,EXTR_PREFIX_SAME, "wddx");

    $db = new DbHandler();

    //updating room
    $result = $db->updateRoom($id, $room_id, $homestay, $price, $address, $num_rooms, $smoking, $alcohol, $kitchen, $internet, $breakfast, $description);
    
    if ($result) {
        //room updated successfully
        $response["error"] = false;
        $response["message"] = "Room updated successfully";
    } else {
        //room failed to update
        $response["error"] = true;
        $response["message"] = "Room updated failed"; 
    }
    echoRespnse(200, $response);
});

/** Delecing Room. owner can delete only their room
 */
 $app->delete('/rooms/:id', 'authenticate', function($room_id) use ($app){
    global $owner_id;
    $db = new DbHandler();
    $response = array();
    //assigns value of id from array(owner_id) to $id 
    extract($owner_id,EXTR_PREFIX_SAME, "wddx");

    $result = $db->deleteRoom($id, $room_id);
    if ($result){
        //room deleted
        $response["error"] = false;
        $response["message"] = "Room removed successfully";
    } else {
        $response["error"] = true;
        $response["message"] = "Failed to remove room";
    }
    echoRespnse(200, $response);

 });
/**
 * Listing single room of particular owner
 * method GET
 * url /rooms/:id
 * Will return 404 if the room doesn't belongs to user
 */
// $app->get('/rooms/:id', 'authenticate', function($room_id){
//     global $owner_id;
//     $response = array();
//     $db = new DbHandler();

//     //fetch room
//     $result = $db->getRoom($room_id, $owner_id);

//     if ($result != NULL){
//         $response["error"] = false;
//         $response["id"] = $result["id"];
//         $response["homestay_name"] = $result["homestay_name"];
//         $response["price"] = $result["price"];
//         $response["address"] = $result["address"];
//         $response["num_rooms"] = $result["num_rooms"];
//         $response["smoking"] = $result["smoking"];
//         $response["alcohol"] = $result["alcohol"];
//         $response["kitchen"] = $result["kitchen"];
//         $response["internet"] = $result["internet"];
//         $response["breakfast"] = $result["breakfast"];
//         $response["description"] = $result["description"];
//         $response["created_at"] = $result["created_at"];
//         echoRespnse(200, $response);
//     } else {
//         $response["error"] = true;
//         $response["message"] = "The requested resource doesn't exist.";
//         echoRespnse(404, $response);
//     }

// });

/**
 * Verifying required params posted or not
 */
function verifyRequiredParams($required_fields) {
    $error = false;
    $error_fields = "";
    $request_params = array();
    $request_params = $_REQUEST;
    // Handling PUT request params
    if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
        $app = \Slim\Slim::getInstance();
        parse_str($app->request()->getBody(), $request_params);
    }
    foreach ($required_fields as $field) {
        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }

    if ($error) {
        // Required field(s) are missing or empty
        // echo error json and stop the app
        $response = array();
        $app = \Slim\Slim::getInstance();
        $response["error"] = true;
        $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
        echoRespnse(800, $response);
        $app->stop();
    }
}

/**
 * Validating email address
 */
function validateEmail($email) {
    $app = \Slim\Slim::getInstance();
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response["error"] = true;
        $response["message"] = 'Email address is not valid';
        echoRespnse(800, $response);
        $app->stop();
    }
}

/**
 * Echoing json response to client
 * @param String $status_code Http response code
 * @param Int $response Json response
 */
function echoRespnse($status_code, $response) {
    $app = \Slim\Slim::getInstance();
    // Http response code
    $app->status($status_code);

    // setting response content type to json
    $app->contentType('application/json');

    echo json_encode($response);
}



$app->run();

?>