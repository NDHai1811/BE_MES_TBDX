<?php

namespace App\Helpers;

class Utilities
{
    public static function columnToIndex($columnName) {
        $columnName = strtoupper($columnName); // Chuyển về chữ in hoa
        $columnNumber = 0;
    
        for ($i = 0; $i < strlen($columnName); $i++) {
            $columnNumber = $columnNumber * 26 + (ord($columnName[$i]) - ord('A') + 1);
        }
    
        return $columnNumber;
    }

    public static function indexToColumn($columnNumber) {
        $columnName = '';
    
        while ($columnNumber > 0) {
            $mod = ($columnNumber - 1) % 26;
            $columnName = chr($mod + ord('A')) . $columnName;
            $columnNumber = intval(($columnNumber - 1) / 26);
        }
    
        return $columnName;
    }
}
