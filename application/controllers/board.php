<?php

class Board extends CI_Controller {
     
    function __construct() {
		// Call the controller constructor
    	parent::__construct();
    	session_start();
    } 
          
    public function _remap($method, $params = array()) {
    	// Enforce access control to protected functions	
		
		if (!isset($_SESSION['user']))
            // Then we redirect to the index page again
			redirect('account/loginForm', 'refresh');
	    	
    	return call_user_func_array(array($this, $method), $params);
    }
    
    
    function index() {
		$user = $_SESSION['user'];
    		    	
	    	$this->load->model('user_model');
	    	$this->load->model('invite_model');
	    	$this->load->model('match_model');
	    	
	    	$user = $this->user_model->get($user->login);

	    	$invite = $this->invite_model->get($user->invite_id);
	    	
	    	if ($user->user_status_id == User::WAITING) {
	    		$invite = $this->invite_model->get($user->invite_id);
	    		$otherUser = $this->user_model->getFromId($invite->user2_id);
	    	}
	    	else if ($user->user_status_id == User::PLAYING) {
	    		$match = $this->match_model->get($user->match_id);
	    		if ($match->user1_id == $user->id)
	    			$otherUser = $this->user_model->getFromId($match->user2_id);
	    		else
	    			$otherUser = $this->user_model->getFromId($match->user1_id);
	    	}
	    	
	    	$data['user']=$user;
	    	$data['otherUser']=$otherUser;
	    	
	    	switch($user->user_status_id) {
	    		case User::PLAYING:	
	    			$data['status'] = 'playing';
	    			break;
	    		case User::WAITING:
	    			$data['status'] = 'waiting';
	    			break;
	    	}
	    	
		$this->load->view('match/board',$data);
    }

 	function postMsg() {
 		$this->load->library('form_validation');
 		$this->form_validation->set_rules('msg', 'Message', 'required');
 		
 		if ($this->form_validation->run() == TRUE) {
 			$this->load->model('user_model');
 			$this->load->model('match_model');

 			$user = $_SESSION['user'];
 			 
 			$user = $this->user_model->getExclusive($user->login);
 			if ($user->user_status_id != User::PLAYING) {	
				$errormsg="Not in PLAYING state";
 				goto error;
 			}
 			
 			$match = $this->match_model->get($user->match_id);			
 			
 			$msg = $this->input->post('msg');
 			
 			if ($match->user1_id == $user->id)  {
 				$msg = $match->u1_msg == ''? $msg :  $match->u1_msg . "\n" . $msg;
 				$this->match_model->updateMsgU1($match->id, $msg);
 			}
 			else {
 				$msg = $match->u2_msg == ''? $msg :  $match->u2_msg . "\n" . $msg;
 				$this->match_model->updateMsgU2($match->id, $msg);
 			}
 				
 			echo json_encode(array('status'=>'success'));
 			 
 			return;
 		}
		
 		$errormsg="Missing argument";
 		
		error:
			echo json_encode(array('status'=>'failure','message'=>$errormsg));
 	}
 
	function getMsg() {
 		$this->load->model('user_model');
 		$this->load->model('match_model');
 			
 		$user = $_SESSION['user'];
 		 
 		$user = $this->user_model->get($user->login);
 		if ($user->user_status_id != User::PLAYING) {	
 			$errormsg="Not in PLAYING state";
 			goto error;
 		}
 		// start transactional mode  
 		$this->db->trans_begin();
 			
 		$match = $this->match_model->getExclusive($user->match_id);			
 			
 		if ($match->user1_id == $user->id) {
			$msg = $match->u2_msg;
 			$this->match_model->updateMsgU2($match->id,"");
 		}
 		else {
 			$msg = $match->u1_msg;
 			$this->match_model->updateMsgU1($match->id,"");
 		}

 		if ($this->db->trans_status() === FALSE) {
 			$errormsg = "Transaction error";
 			goto transactionerror;
 		}
 		
 		// if all went well commit changes
 		$this->db->trans_commit();
 		
 		echo json_encode(array('status'=>'success','message'=>$msg));
		return;
		
		transactionerror:
		$this->db->trans_rollback();
		
		error:
		echo json_encode(array('status'=>'failure','message'=>$errormsg));
 	}

 	function postSlots() {
        $this->load->model('user_model');
        $this->load->model('match_model');

        // This model sets the game rules which will check if the game ends
        $this->load->model('rules_model');
        $user = $_SESSION['user'];

        // Get data from board view
        $x = $this->input->post('X');
        $y = $this->input->post('Y');
        $colNum = $this->input->post('colNum');
        $index = \intval($x) + \intval($colNum) * \intval($y);

        // Get user
        $user = $this->user_model->getExclusive($user->login);
        if ($user->user_status_id != User::PLAYING) {
            $errormsg = "Not in PLAYING state";
            goto error;
        }

        // Start the transactional mode  
        $this->db->trans_begin();
        $match = $this->match_model->getExclusive($user->match_id);
        $blob = $match->board_state;

        // Get the current user
        if ($match->user1_id == $user->id){
            $currentUser = 0; // If it is user1 of the match, then currentUser is 0
        }else{
            $currentUser = 1; // Otherwise currentUser is 1
        }

        // Initialize the game
        if ($blob == NULL){

            // Get the index of the location where the player placed the piece
            $index = $this->rules_model->placeMove(NULL, $x);
                if ($currentUser == 1){
                    $index += 42; // this is the index for user 2 
                } 
                // key -1 stores the value of the currentUser
                $board_info = array(0 => $index, -1 => $currentUser);
                $blob = serialize($board_info);
                $this->match_model->makeBoard($match->id, $blob);
        } 
        // During the game
        else { 
            $board_info = unserialize($blob);

            // board_info[-1] stores the current user.
            if ($board_info[-1] != $currentUser) {
                $index = $this->rules_model->placeMove($board_info, $x);
                    if ($currentUser == 1){
                        $index += 42; // this is the index for user 2 
                    }
                    $isFilled = false; // check if this slot is filled already
                    $newkey = 0; // a new key for the blob
                    foreach ($board_info as $key => $value) {
                        if ($value == $index && $key >= 0) {
                            $isFilled = true;}
                        if($key >= 0){
                            $newkey = $key;}
                    }
                    $newkey += 1; // get the new key

                    // this is a new index within the grid, then the player can place the piece
                    if (!$isFilled) {
                        $board_info[$newkey] = $index;
                        $board_info[-1] = $currentUser;
                        $blob = serialize($board_info);
                        $this->match_model->makeBoard($match->id, $blob);
                        $userWin = $this->rules_model->playerWon($board_info);

                        // The game has to end, check the result
                        if($userWin > 0){
                            if($userWin == 1){ // user 1 wins
                                $this->match_model->updateStatus($match->id, 2);
                            }else if ($userWin ==2) { //user 2 wins
                                $this->match_model->updateStatus($match->id, 3);
                            }else { // tie game (board is filled)
                                $this->match_model->updateStatus($match->id, 4);
                            }
                        }
                    }
               
            }
        }
        
        if ($this->db->trans_status() === FALSE) {
            $errormsg = "Transaction error";
            goto transactionerror;
        }

        // if all went well commit changes
        $this->db->trans_commit();
        
        echo json_encode(array('status' => 'success'));
        return;
        
        transactionerror:
        $this->db->trans_rollback();
        
        error:
        echo json_encode(array('status' => 'failure', 'message' => $errormsg));
    }

 	function getSlots() {
        $this->load->model('user_model');
        $this->load->model('match_model');

        $user = $_SESSION['user'];

        $user = $this->user_model->get($user->login);
        if ($user->user_status_id != User::PLAYING) {
            $errormsg = "Not in PLAYING state";
            goto error;
        }

        $match = $this->match_model->getExclusive($user->match_id);
        $msg = "works";
        $blob = $match->board_state;

        // Blob is a binary data block
        if ($blob == NULL) {
            $errormsg = "Blob error";
            goto error;
        } 
        // We get the data of blob successfully
        else { 
            $board_slots = unserialize($blob);
            $size = 0; // the size of the blob
            foreach ($board_slots as $key => $value) {
                if ($key < 0) // key may be < 0 because we have a key -1 to store the current User
                    continue;
                $size++;
            }
            $blob_json = json_encode($board_slots);
        }
        
        //get the game's status
        $matchStatusId = $match->match_status_id; 
        $matchStatus = 'active';

        // if the game is over then the users are free to play another match
        if($matchStatusId == 2) {
            $matchStatus = 'user1Won';
            $this->user_model->updateStatus($user->id,User::AVAILABLE);
        }
        else if ($matchStatusId == 3) {
            $matchStatus = 'user2Won';
            $this->user_model->updateStatus($user->id,User::AVAILABLE);
        }
        else if ($matchStatusId == 4) {
            $matchStatus = 'tie';
            $this->user_model->updateStatus($user->id,User::AVAILABLE);
        }

        //get the login of the two players in the match
        $user1Login = $this->user_model->getFromId($match->user1_id)->login;
        $user2Login = $this->user_model->getFromId($match->user2_id)->login;
        
        // encode the json
        echo json_encode(array('status' => 'success', 'message' => $msg, 'blob' => $blob_json, 'size' => $size, 'red' => base_url("images/red.png"), 'yellow' => base_url("images/yellow.png"), 'match_status'=>$matchStatus, 'user1Login'=>$user1Login, 'user2Login'=>$user2Login));
        return;
        
        error:
        echo json_encode(array('status' => 'failure', 'message' => $errormsg));
    }
 	
 }

