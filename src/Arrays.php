<?php

namespace Saffyre;

class Arrays {

    /**
     * Converts $array into an array if it is not already.
     */
    public static function ensure($array) {
        if($array === null) return array();
        if(!is_array($array)) return array($array);
        return $array;
    }

    /**
     * Recursively searches $haystack for $needle, including nested arrays.
     */
    public static function in_array_recursive($needle, $haystack, $strict = false) {
        $found = in_array($needle, $haystack, $strict);
        if($found) return $found;
        if(!$found)
            foreach($haystack as $value)
                if(is_array($value)) $found = self::in_array_recursive($needle, $value, $strict);
        return $found;
    }

    /**
     * Removes all elements from $array that match $badval, and shifts every element to the front of the array.
     * The array size is still preserved. Empty values on the end of array are filled with $emptyval.
     */
    public static function array_compact($array, $badval = null, $emptyval = null, $strict = false) {
        $new = array();
        foreach($array as $value)
            if(($strict && $badval == $value) || (!$strict && $value === $badval)) $new[] = $value;
        return array_pad($new, count($array), $emptyval);
    }


    /**
     * Unsets all elements that match $remove.
     */
    public static function array_clean($array, $remove = null, $strict = false) {
        foreach($array as $key => $value)
            if(	(!is_array($remove) &&  $strict && $value === $remove) ||
                (!is_array($remove) && !$strict && $value == $remove) ||
                (is_string($remove) && strlen($remove > 2) && (substr($remove, 0, 1) == (substr($remove, -1) == '/')) && preg_match($remove, $value)) ||
                ( is_array($remove) && in_array($value, $remove, $strict)))
                unset($array[$key]);
        return $array;
    }

    /**
     * Returns whether the array has any values other than null, works recursively
     */
    public static function array_isempty($array, $strict = false) {
        $empty = true;
        foreach($array as $a) {
            if(is_array($a)) $empty = Util::array_isempty($a, $strict);
            elseif((!$strict && $a) || ($strict && $a !== null)) $empty = false;
        }
        return $empty;
    }


    /**
     * Returns an array with non-unique elements removed.
     * @param array $array
     */
    public static function array_unique(&$array)
    {
        foreach($array as $key => $value)
            if(array_search($value, $array, true) != $key)
                unset($array[$key]);
        return $array;
    }

    public static function implode($glue, $array, $last = null) {
        if($last === null) $last = $glue;
        if(!$array) return '';
        if(count($array) == 1) return reset($array);
        $parts = array_chunk($array, count($array) - 1);
        return implode($glue, $parts[0]) . $last . reset($parts[1]);
    }

}
