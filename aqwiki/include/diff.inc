<?php

function diff($text1, $text2){
	$diff = explode("\n",diff_compute($text1, $text2));
	$out = "<ol class=\"diffSection\">";
	foreach($diff as $line){
		if (is_numeric($line[0])){
			$out.= "</ol><ol class=\"diffSection\">";
		} else {
			switch ($line[0]){
			case "<":
				$out .=  "<li class=\"diff added\">". $nr1. " : $nr2 : + ".htmlentities( $line )  ."</li>\n";
			break;

			case ">":
				$out .=  "<li class=\"diff removed\">". $nr1. " : $nr2 : - ".htmlentities( $line )  ."</li>\n";
			break;

			case "-":
			break;
			
			default:
				$out .= "<li class=\"diff\">". $nr1. " : $nr2 : = ".htmlentities( $line )  ."</li>\n";
			break;

			}
		}
	}
	$out .= "</ol>";

	return $out;
}


// $Id$ Modified by PAH 2004-04-11

// Compute the difference between two sets of text.
function array_minus_array($a, $b) {
      $c=array_diff($a,$b);
       $c=array_intersect($c, $a);
      return $c;
}

function diff_compute($text1, $text2)
{

  #global $TempDir, $DiffCmd;

  $TempDir = "/tmp";
  $DiffCmd = "/usr/bin/diff";

  $num = function_exists('posix_getpid') ? posix_getpid() : rand();

  $temp1 = $TempDir . '/aqWiki_' . $num . '_1.txt';
  $temp2 = $TempDir . '/aqWiki_' . $num . '_2.txt';

  if(!($h1 = fopen($temp1, 'w')) || !($h2 = fopen($temp2, 'w')))
    { die(LIB_ErrorCreatingTemp); }

  if(fwrite($h1, $text1) < 0 || fwrite($h2, $text2) < 0)
    { die(LIB_ErrorWritingTemp); }

  fclose($h1);
  fclose($h2);

  if (ini_get('safe_mode') and
     (ini_get('safe_mode_exec_dir') != dirname($DiffCmd))) 
    { $diff = LIB_NoDiffAvailableSafeMode; }
  else if (!file_exists($DiffCmd) or !is_readable($DiffCmd)) 
    { $diff = LIB_NoDiffAvailable; }
  else {
    $output = array();
    exec("$DiffCmd $temp1 $temp2", $output);
    $diff = join("\n", $output);
  }

  unlink($temp1);
  unlink($temp2);

  return $diff;


  //and now if temp files don't work:
  $texta=explode ("\n", $text1);
  $textb=explode ("\n", $text2);
  $diffab = "<TABLE>". //<TR><TD>Old</TD><TD>New</TD></TR><TR><TD>$text1</TD><TD>$text2</TD></TR>
  "<TR><TD>Removed:</TD><TD>Added:</TD></TR>
           <TR><TD>".nl2br(implode ("\n", array_minus_array ($texta, $textb)))."</TD>
             <TD>".nl2br(implode ("\n", array_minus_array ($textb, $texta)))."
             </TD></TR></TABLE>";
  return "<HR>".$diffab."<HR>";
}

// Parse diff output into nice HTML.
function diff_parse($text)
{
  global $DiffEngine;

//  return parseText($text, $DiffEngine, '');
  return $text;
}

?>