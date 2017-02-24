<?php

namespace app\components;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;

define('RANGE_ARRAY_SORT', 1);
define('RANGE_ARRAY', 2);
define('RANGE_STRING_SORT', 3);
define('RANGE_STRING', 4);

class Tools extends Component {

   public function rangeString($range_str, $column, $output_type = RANGE_ARRAY_SORT) {

      $sql = [];
      $in = [];

      $range_str = str_replace("\n", ",", $range_str);
      $range_str = str_replace(" ", "", $range_str);

      // Remove spaces and nother non-essential characters
      $find[] = "/[^\d,\-\*]/";
      $replace[] = "";

      // Remove duplicate hyphens
      $find[] = "/\-+/";
      $replace[] = "-";

      // Remove duplicate commas
      $find[] = "/\,+/";
      $replace[] = ",";

      $range_str = preg_replace($find, $replace, $range_str);

      // Remove any commas or hypens from the end of the string
      $range_str = trim($range_str, ",-");

      $range_out = array();
      $ranges = explode(",", $range_str);

      foreach ($ranges as $range) {

         if (is_numeric($range) || strlen($range) == 1) {
            // Just a number; add it to the list.
            $in[] = $range;
            $range_out[] = (int) $range;
         } else if (is_string($range)) {

            $stars = 0;
            $minus = 0;

            $chars = str_split($range);

            foreach ($chars as $c) {
               if (($c > '9' || $c < '0') && $c != '*' && $c != '-') {
                  //die('ee');
                  return -1;
               }

               if ($c == '*') {
                  $stars++;
               } else
               if ($c == '-') {
                  $minus++;
               }
            }



            if ($minus == 1) {
               // Is probably a range of values.
               $range_exp = preg_split("/(\D)/", $range, -1, PREG_SPLIT_DELIM_CAPTURE);

               $start = $range_exp[0];
               $end = $range_exp[2];


               //die($start. ' '. $end);

               if ($start > $end) {
                  $sql[] = "$column <= '$start' AND $column >= '$end'";
                  /* for ($i = $start; $i >= $end; $i -= 1) {
                    $range_out[] = (int) $i; */
                  //}
               } else {
                  $sql[] = "$column >= '$start' AND $column <= '$end'";
                  /* for ($i = $start; $i <= $end; $i += 1) {
                    $range_out[] = (int) $i; */
                  //}
               }
            } else if ($stars) {

               $range = str_replace('*', '_', $range) . '%';
               $sql[] = "$column LIKE '$range'";
            } else
               return -1;
         }
      }


      foreach ($in as $k => $v) {
         $in[$k] = "'" . $v . "'";
      }

      $sql = implode(' AND ', $sql);
      if (count($in)) {
         if (strlen($sql) > 0)
            $sql.=' AND ';
         $sql.="$column IN (" . implode(',', $in) . ')';
      }

      return $sql;

      switch ($output_type) {
         case RANGE_ARRAY_SORT:
            $range_out = array_unique($range_out);
            sort($range_out);

         case RANGE_ARRAY:
            return $range_out;
            break;

         case RANGE_STRING_SORT:
            $range_out = array_unique($range_out);
            sort($range_out);

         case RANGE_STRING:

         default:
            return implode(", ", $range_out);
            break;
      }
   }

}
