<?php

class Board extends CI_Controller {
     
    function __construct() {
    		// Call the Controller constructor
	    	parent::__construct();
	    	session_start();
    } 
          
    public function _remap($method, $params = array()) {
	    	// enforce access control to protected functions	
    		
    		if (!isset($_SESSION['user']))
   			redirect('account/loginForm', 'refresh'); //Then we redirect to the index page again
 	    	
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
        // this modle set the game rules.
        $this->load->model('rules_model');

        $user = $_SESSION['user'];

        $indexX = $this->input->post('indexX');
        $indexY = $this->input->post('indexY');
        $colNum = $this->input->post('colNum');
        $index = \intval($indexX) + \intval($colNum) * \intval($indexY);

        $user = $this->user_model->getExclusive($user->login);
        if ($user->user_status_id != User::PLAYING) {
            $errormsg = "Not in PLAYING state";
            goto error;
        }

        // start transactional mode  
        $this->db->trans_begin();

        $match = $this->match_model->getExclusive($user->match_id);
        $blob = $match->board_state;
        $currentUser = 0;

        if ($match->user2_id == $user->id) {
            $currentUser = 1;
        }

        if ($blob != NULL) {
            $boardArray = unserialize($blob);
            if ($boardArray[-1] != $currentUser) {
                $index = $this->rules_model->placeMove($boardArray, $indexX, $indexY);
                if ($index >= 0) {
                    if ($currentUser == 1)
                        $index += 42;
                    $slotFilled = false;
                    $maxkey = 0;
                    foreach ($boardArray as $key => $value) {
                        if ($value == $index && $key >= 0) {
                            $slotFilled = true;
                        }
                        if($key >= 0)$maxkey = $key;
                    }
                    $maxkey += 1;
                    if (!$slotFilled) {
                        $boardArray[$maxkey] = $index;
                        $boardArray[-1] = $currentUser;
                        $boardBlob = serialize($boardArray);
                        $this->match_model->insertBoard($match->id, $boardBlob);
                        $userWin = $this->rules_model->checkWin($boardArray);
                        if($userWin > 0){
                            if($userWin == 1){
                                $this->match_model->updateStatus($match->id, 2);
                            }
                            else {
                                $this->match_model->updateStatus($match->id, 3);
                            }
                        }
                    }
                }
            }
        } else if ($currentUser == 0) {
            $index = $this->rules_model->placeMove(NULL, $indexX, $indexY);
            if ($index >= 0) {
                if ($currentUser == 1)
                    $index += 42;
                $boardArray = array(0 => $index, -1 => $currentUser);
                $boardBlob = serialize($boardArray);
                $this->match_model->insertBoard($match->id, $boardBlob);
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
        // blob is a binary data block. 
        if ($blob == NULL) {
            $errormsg = "Blob error";
            goto error;
        } else {
            $board_slots = unserialize($blob);
            $size = 0;
            foreach ($board_slots as $key => $value) {
                if ($key < 0)
                    continue;
                $size++;
            }
            $blob_json = json_encode($board_slots);
        }
        
        $matchStatusId = $match->match_status_id;
        $matchStatus = 'active';
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
        $user1Login = $this->user_model->getFromId($match->user1_id)->login;
        $user2Login = $this->user_model->getFromId($match->user2_id)->login;
        
        echo json_encode(array('status' => 'success', 'message' => $msg, 'blob' => $blob_json, 'size' => $size, 'red' => base_url("images/red.png"), 'yellow' => base_url("images/yellow.png"), 'match_status'=>$matchStatus, 'user1Login'=>$user1Login, 'user2Login'=>$user2Login));
        return;
        
        error:
        echo json_encode(array('status' => 'failure', 'message' => $errormsg));
    }
 	
 }

