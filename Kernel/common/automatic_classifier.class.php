<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/automatic_classifier.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
include_once(dirname(__FILE__)."/../common/common_functions.inc.php");

class automatic_classifier{
	// largely inspired by PHP Naive Bayesian Filter class. //
	var $min_token_length = 3;
	var $max_token_length = 15;
	var $highest_proba_tokens_number = 15;
	
	var $db_words 			= 'filter_words';
	var $db_filter_categs 	= 'filter_categories';
	var $db_stopwords 		= 'filter_stopwords';
	
	var $stop_words = array();
	
	var $computing_problems = false;
	
	function automatic_classifier(){
		$this->build_stop_words();
	}
	
	function train($document,$category,$word_weight=1){
		//debug_log('train');
		$tokens = $this->get_tokens($document,$word_weight);
        while (list($token, $count) = each($tokens)) {
            $this->update_word($token, $count, $category);
        }
	}
	
	function untrain($document,$category,$word_weight=1){
		//debug_log('untrain');
		$tokens = $this->get_tokens($document,$word_weight);
        while (list($token, $count) = each($tokens)) {
            $this->update_word($token, -$count, $category);
        }
	}
	
	function get_categories(){
		$db_conn = db_connect();
        $categories = array();
        $rs = $db_conn->Execute('SELECT * FROM `'.$this->db_filter_categs.'`');
        while ($row = $rs->FetchRow()) {
            $categories[$row['category']] = array('probability' => $row['probability'],'word_count'  => $row['word_count']);
        }
        return $categories;
    }
	
	function classify($document){
		// taken from PHP Naive Bayesian Filter class. //
        $scores = array();
        $categories = $this->get_categories();
        $tokens = $this->get_tokens($document);
        // calculate the score in each category
        $total_words = 0;
        $ncat = 0;
        while (list($category, $data) = each($categories)) {
            $total_words += $data['word_count'];
            $ncat++;
        }
        reset($categories);
        while (list($category, $data) = each($categories)) {
            //$scores[$category] = $data['probability'];
			$scores[$category] = 1; // because it's misleading and varies from hours to hours, see : http://www.paulgraham.com/better.html
			//debug_log('proba for category '.$category.' is '.$data['probability']);
            // small probability for a word not in the category
            // maybe putting 1.0 as a 'no effect' word can also be good
			if($data['word_count'])
            	$small_proba = 1.0/($data['word_count']*2); // lissage
			else
				$small_proba = 0;
			$highest_interest_tokens = array(false);
			$highest_interest_diffs = array(false);
			$highest_interest_probas = array(false);
			$highest_interest_counts = array(false);
			$smallest_diff = 0;
            reset($tokens);
            while (list($token, $count) = each($tokens)) {
                if ($this->word_exists($token)) {
                    $word = $this->get_word($token, $category);
                    if ($word['count'] && $data['word_count']) $proba = $word['count']/$data['word_count'];
                    else {$proba = $small_proba;}
                    //$scores[$category] *= pow($proba, $count)*pow($total_words/$ncat, $count);
                    // pow($total_words/$ncat, $count) is here to avoid underflow.
					//debug_log('proba is '.$proba.'for word '.$token);
					$diff_to_middle = abs(0.5-$proba);
					
					$size = sizeof($highest_interest_diffs);
					if($diff_to_middle>$smallest_diff || $size < 15 ){
						for($i=0;$i<$size;$i++){
							if($diff_to_middle>$highest_interest_diffs[$i]){
								break;
							}
						}
						if($i<$size){
							if($size>=15){
								array_pop($highest_interest_diffs);
								array_pop($highest_interest_tokens);
								array_pop($highest_interest_counts);
								array_pop($highest_interest_probas);
							}
							array_splice($highest_interest_diffs,$i,0,$diff_to_middle);
							array_splice($highest_interest_counts,$i,0,$count);
							array_splice($highest_interest_probas,$i,0,$proba);
							array_splice($highest_interest_tokens,$i,0,$token);
							if($i==0)
								$smallest_diff = $diff_to_middle;
							//
							//debug_log('this is a high interest token '.$token.' '.$proba.' '.$diff_to_middle.' '.sizeof($highest_interest_tokens).' '.$highest_interest_tokens[$i]);
						}
					}
                }
            }
			$j=1;
			for($j=0;$j<sizeof($highest_interest_tokens);$j++){
				$token = $highest_interest_tokens[$j];
				if($token){
					$proba = $highest_interest_probas[$j];
					$count = $highest_interest_counts[$j];
					//debug_log('final highest interest token '.$j.' '.$token.'-'.$proba.'-'.$count);
					$scores[$category]*= pow($proba, $count)*pow($total_words/$ncat, $count);
				}
				//$j++;
			}
			if(is_nan($scores[$category]) || is_infinite($scores[$category]))
				$this->computing_problems = true;
			//debug_log('final score '.$str.' for '.$category.' is '.$scores[$category]);
        }
        return $this->rescale($scores);
    }
	
	function get_tokens($string,$word_weight=1){
		// taken from PHP Naive Bayesian Filter class. //
		$rawtokens = array();
        $tokens    = array();
        $string = removeaccents($string); // keeping case, because Spam often uses uppercase in a determined manner
        
        $rawtokens = split("[^-_A-Za-z0-9]+", $string);
        // remove some tokens
        while (list( , $token) = each($rawtokens)) {
            $token = trim($token);
            if (!(('' == $token)                             ||
                  (strlen($token) < $this->min_token_length) ||
                  (strlen($token) > $this->max_token_length) ||
                  (preg_match('/^[0-9]+$/', $token))         ||
                  (in_array($token, $this->stop_words))
               ))
               $tokens[$token]+=$word_weight;
        }
        return $tokens;
	}
	
	function rescale($scores){
		// taken from PHP Naive Bayesian Filter class. //
        // Scale everything back to a reasonable area in 
        // logspace (near zero), un-loggify, and normalize
        $total = 0.0;
        $max   = 0.0;
        reset($scores);
        while (list($cat, $score) = each($scores)) {
            if ($score >= $max) $max = $score;
        }
        reset($scores);
        while (list($cat, $score) = each($scores)) {
            $scores[$cat] = (float) exp($score - $max);
            $total += (float) pow($scores[$cat],2);
        }
        $total = (float) sqrt($total);
        reset($scores);
        while (list($cat, $score) = each($scores)) {
             $scores[$cat] = (float) $scores[$cat]/$total;
        }
        reset($scores);
        return $scores;
    }
	
	function build_stop_words(){
		$db_conn = db_connect();
		$rs = $db_conn->Execute('SELECT `word` FROM `'.$this->db_stopwords.'`');
		$this->stop_words = array();
		if($rs){
			while($row = $rs->FetchRow()){
				$this->stop_words[]=$row['word'];
			}
		}
	}
	
	function get_word($token,$category){
		$db_conn = db_connect();
		$word = array();
		$sql = 'SELECT * FROM `'.$this->db_words.'` WHERE `word`="'.encodeQuote($token).'" AND `category`=\''.$category.'\'';
		//debug_log($sql);
        $row = $db_conn->GetRow($sql);
        if (!$row) $word['count'] = 0;
        else $word['count'] = $row['count'];
		//debug_log($word['count']);
        return $word;
	}
	
	function update_word($word,$count,$category){
		$db_conn = db_connect();
		$former = $this->get_word($word,$category);
		//debug_log('count '.$count.' former '.$former['count']);
		if($former['count']==0 && $count>0){
			$sql = 'INSERT INTO `'.$this->db_words.'`(`word`,`category`,`count`) VALUES("'.encodeQuote($word).'",\''.$category.'\',\''.$count.'\')';
		}else if($count<0 && ($former['count']+$count)>0){
			$sql = 'UPDATE `'.$this->db_words.'` SET count=count-'.(-$count).' WHERE `word`="'.encodeQuote($word).'" AND `category`=\''.$category.'\'';
		}else if($count>0 && $former['count']>0){
			$sql = 'UPDATE `'.$this->db_words.'` SET count=count+\''.$count.'\' WHERE `word`="'.encodeQuote($word).'" AND `category`=\''.$category.'\'';
		}else{
			$sql = 'DELETE FROM `'.$this->db_words.'` WHERE `word`="'.encodeQuote($word).'" AND `category`=\''.$category.'\'';
		}
		$db_conn->Execute($sql);
		//debug_log($sql);
	}
	
	function word_exists($word){
		$db_conn = db_connect();
		$row = $db_conn->GetRow("SELECT * FROM `".$this->db_words."` WHERE `word`='".encodeQuote($word)."'");
		if (!$row)
			return false;
		else
			return true;
	}
	
	function update_categories_informations(){
		$db_conn = db_connect();
		$db_conn->Execute('UPDATE `'.$this->db_filter_categs.'` SET `word_count`=0, `probability`=0 WHERE 1');
		$rs = $db_conn->Execute("SELECT category, SUM(count) AS total FROM `".$this->db_words."` WHERE 1 GROUP BY category");
        $total_words = 0;
		$category_words = array();
        while ($row = $rs->FetchRow()) {
            $total_words += $row['total'];
			$category_words[$row['category']]=$row['total'];
        }
       foreach($category_words as $key=>$value){
            $proba = $value/$total_words;
            $db_conn->Execute("UPDATE `".$this->db_filter_categs."` SET `word_count`='".(int)$value."',`probability`='".$proba."' WHERE category = '".$key."'");
        }
		return true;
	}
}

?>
