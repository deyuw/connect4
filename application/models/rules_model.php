<?php

class rules_model extends CI_Model {

    // Insert new piece into the field
    function placeMove($field, $column) { // placeMove/insertPiece ///////////////////////////
        if ($field != NULL) {
            $newRow = 5;
            $newColumn = $column;
            foreach ($field as $key => $val) {
                if ($key < 0){
                    continue;
                }

                // Determine who owns the piece
                $newVal = $val;
                if ($val >= 42)
                    $newVal = $val - 42;

                // Get the current column and row of that piece
                $currCol = (int) ($newVal % 7);
                $currRow = (int) floor($newVal / 7);

                // Set the piece's location
                if ($newColumn == $currCol) {
                    $newCurrRow = $currRow - 1;
                    if ($newCurrRow < $newRow)
                        $newRow = $newCurrRow;
                }
            }

            // Return the index of the piece
            return $newColumn + 7 * $newRow;
        } else {
            return intval($column) + 7 * 5;
        }
    }

    function checkWin($field) { // checkWin/playerWon ////////////////////////////////////////
        $boardA = array(array());
        $boardB = array(array());

        // Check whether the first player won
        for ($col=0; $col<7; $col++) {
            for ($row=0; $row<6; $row++) {
                // create a board consisting of only the first player's pieces
                $keys = array_keys($field, $col + 7 * $row);
                $used = false;
                foreach ($keys as $val) {
                    if ($val >= 0) {
                        $used = true;
                    }
                }
                if ($used == true) {
                    $boardA[$col][$row] = 1;
                } else {
                    $boardA[$col][$row] = 0;
                }
            }
        }
        // Check if this board has four pieces together
        if ($this->checkWinBoard($boardA)){ ///////////////////////////////////////////////////
            return 1;
        }

        // Check whether the second player won
        for ($col = 0; $col < 7; $col++) {
            for ($row = 0; $row < 6; $row++) {
                // create a board consisting of only the first player's pieces
                $keys = array_keys($field, $col + 7 * $row + 42);
                $used = false;
                for ($i = 0; $i < count($keys); $i++) {
                    if ($keys[$i] >= 0) {
                        $used = true;
                    }
                }
                if ($used) {
                    $boardB[$col][$row] = 1;
                } else {
                    $boardB[$col][$row] = 0;
                }
            }
        }
        // Check if this board has four pieces together
        if ($this->checkWinBoard($boardB)){ ////////////////////////////////////////////////////
            return 2;
        }

        // Check if the entire field has been filled
        for ($col = 0; $col < 7; $col++) {
            for ($row = 0; $row < 6; $row++) {
                if ($boardA[$col][$row] == 0 && $boardB[$col][$row] == 0){
                    // there are still free spaces on the board
                    return 0;
                }
            }
        }

        // The entire field has been filled -> tie game
        return 3;
    }

    function checkWinBoard($field) { // checkWinBoard/boardEnd ///////////////////////////////
        $end = false;
        for ($col = 0; $col < 7; $col++) {
            for ($row = 0; $row < 6; $row++) {
                // see if there is a row of 4 pieces of the same color
                if ($col < 4) {
                    if ($field[$col][$row] == 1 &&
                        $field[$col + 1][$row] == 1 &&
                        $field[$col + 2][$row] == 1 &&
                        $field[$col + 3][$row] == 1) {
                        $end = true;
                    }
                }
                
                // see if there is a column of 4 pieces of the same color
                if ($row < 3) {
                    if ($field[$col][$row] == 1 &&
                        $field[$col][$row + 1] == 1 &&
                        $field[$col][$row + 2] == 1 &&
                        $field[$col][$row + 3] == 1) {
                        $end = true;
                    }
                }

                // see if there is a diagonal from the bottom left to the top right
                if ($row < 3 && $col > 2) {
                    if ($field[$col][$row] == 1 &&
                        $field[$col - 1][$row + 1] == 1 &&
                        $field[$col - 2][$row + 2] == 1 &&
                        $field[$col - 3][$row + 3] == 1) { 
                        $end = true;
                    }
                }
                
                // see if there is a diagonal from the top left to the bottom right
                if ($row < 3 && $col < 4) {
                    if ($field[$col][$row] == 1 &&
                        $field[$col + 1][$row + 1] == 1 &&
                        $field[$col + 2][$row + 2] == 1 &&
                        $field[$col + 3][$row + 3] == 1) {
                        $end = true;
                    }
                }
            }
        }
        return $end;
    }
}

?>
