<?php

class rules_model extends CI_Model {

    function placeMove($board, $x, $y) {
        if ($board == NULL) {
            return intval($x) + 7 * 5;
        } else {
            $newx = $x;
            $newy = 5;
            foreach ($board as $key => $value) {
                if ($key < 0)
                    continue;
                $newvalue = $value;
                if ($value >= 42)
                    $newvalue = $value - 42;
                $currentIndexX = (int) ($newvalue % 7);
                $currentIndexY = (int) floor($newvalue / 7);
                if ($newx == $currentIndexX) {
                    $newcurrentIndexY = $currentIndexY - 1;
                    if ($newcurrentIndexY < $newy)
                        $newy = $newcurrentIndexY;
                }
            }
            return $newx + 7 * $newy;
        }
    }

    function checkWin($board) {
        
        
        //start with first player
        $userBoard = array(array());
        for ($i = 0; $i < 7; $i++) {
            for ($j = 0; $j < 6; $j++) {
                $isSet = array_keys($board, $i + 7 * $j);
                $isFilled = false;
                foreach ($isSet as $value) {
                    if ($value >= 0) {
                        $isFilled = true;
                    }
                }
                if ($isFilled) {
                    $userBoard[$i][$j] = 1;
                } else {
                    $userBoard[$i][$j] = 0;
                }
            }
        }
        
        
        if ($this->checkWinBoard($userBoard))
            return 1;

        $user2Board = array(array());
        for ($i = 0; $i < 7; $i++) {
            for ($j = 0; $j < 6; $j++) {
                $isSet = array_keys($board, $i + 7 * $j + 42);
                $isFilled = false;
                for ($k = 0; $k < count($isSet); $k++) {
                    if ($isSet[$k] >= 0) {
                        $isFilled = true;
                    }
                }
                if ($isFilled) {
                    $user2Board[$i][$j] = 1;
                } else {
                    $user2Board[$i][$j] = 0;
                }
            }
        }
        if ($this->checkWinBoard($user2Board))
            return 2;

        return 0;
    }

    function checkWinBoard($userBoard) {
        $result = false;
        for ($i = 0; $i < 7; $i++) {
            for ($j = 0; $j < 6; $j++) {
                //check horizontal
                if ($i < 4) {
                    if ($userBoard[$i][$j] == 1 && $userBoard[$i + 1][$j] == 1 && $userBoard[$i + 2][$j] == 1 && $userBoard[$i + 3][$j] == 1) {
                        $result = true;
                        //return $result;
                    }
                }
                
                //check vertical
                if ($j < 3) {
                    if ($userBoard[$i][$j] == 1 && $userBoard[$i][$j + 1] == 1 && $userBoard[$i][$j + 2] == 1 && $userBoard[$i][$j + 3] == 1) {
                        $result = true;
                       // return $result;
                    }
                }
                
                //check diagonal (top-left to bottom-right)
                if ($j < 3 && $i < 4) {
                    if ($userBoard[$i][$j] == 1 && $userBoard[$i + 1][$j + 1] == 1 && $userBoard[$i + 2][$j + 2] == 1 && $userBoard[$i + 3][$j + 3] == 1) {
                        $result = true;
                        return $result;
                    }
                }
                
                //check diagonal (top-right to bottom-left)
                if ($j < 3 && $i > 2) {
                    if ($userBoard[$i][$j] == 1 && $userBoard[$i - 1][$j + 1] == 1 && $userBoard[$i - 2][$j + 2] == 1 && $userBoard[$i - 3][$j + 3] == 1) {
                        $result = true;
                        //return $result;
                    }
                }
                
            }
        }
        return $result;
    }

}

?>
