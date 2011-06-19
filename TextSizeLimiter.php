<?php
/**
 * Text Size Limiter Object
 * By Kfir Breger, 2011
 *
 * A class that facilitates limiting the amount of visible characters
 * in a string. This class assumes well formatted html.
 * To us create an instance of the class, use the load function to load the string and the setLimit function
 * to set the amount of visible characters wanted.
 * Calling run will return the new, shortened string.
 * The object will preserve the short string until another call to run is made. by calling getShortText() it is
 * possible to keep retrieving the shortened version. Note that there is no check made to see if a shortened
 * string was generated. Calling this before any short string is generated will return an empty string.
 *
 */

// Class definition
class TextSizeLimiter {
	// Properties
	private $limit;
	private $current;
	private $walker;
	private $total;
	private $stack;
	private $source;
	private $text;
	private $log_level;
	private $log_stack;
	
	// Constructor
	function __construct() {
		$this->stack = array();
		$this->current = 0;
		$this->walker = 0;
		$this->text = "";
		$this->log_level = 0;
		$this->log_stack = array();
	}
	// Logging
	public function setLog($l) {
		if (is_int($l)) {
			$this->log_level = $l;
		} else {
			$this->log('Setting of log requires an int as input');
		}
	}
	
	public function getLog() {
		return $this->log_level;
	}
	
	private function log($str) {
		switch ($this->log_level) {
			case 1:
				print $str . "\n";
				break;
			case 2:
				$this->log_stack[] = $str;
				break;
		}
	}
	
	public function getLogStack() {
		return $this->log_stack;
	}
	
	// Returning the shortened string
	public function getShortText() {
		return $this->text;
	}
	// Loading string
	public function load($str) {
		if (is_string($str)) {
			$this->source = $str;
			$this->total = strlen($this->source);
		}
		else {
			$this->log('please load a string');
		}
	}
	// Setting limit
	public function setLimit($l) {
		if (is_int($l)) {
			$this->limit = $l;
		} else {
			$this->log('Limit must be an int');
		}
	}
	public function getLimit() {
		return $this->limit;
	}
	
	// Starting the slicing process
	public function run() {
		// checking if a string was given at all;
		if (!is_string($this->source)) {
			$this->log('No string given, terminating');
			return FALSE;
		}
		if (!is_int($this->limit)) {
			// checking if a limit was set
			$this->log('No limit is set so nothing was shortened');
			return $this->source;
		}
		if ($this->limit == 0) {
			// 0 length string
			return '';
		}
		// Checking to see if any shortening is needed at all
		if (abs($this->limit) >= strlen($this->source)) {
			return $this->source;
		}
		// If a negative numer was given figuring out what the required length is
		if ($this->limit < 0) {
			$this->limit += strlen($this->source);
		}
		// Removing old values
		$this->text = '';
		$this->walker = 0;
		$this->current = 0;
		// all tests are made. It is time to start cutting
		$this->analyse();
		
		return $this->text;
	}
	// Doing actual work
	private function analyse() {
		$this->walker = 0;
		$this->text = '';
		$in_tag = FALSE;
		$in_elem = FALSE;
		$build_tag = TRUE;
		$close_tag = FALSE;
		$tag = '';
		while ($this->walker < $this->total) {
			$char = $this->source{$this->walker};
			// If its a tag do not add to clean
			if ($in_tag) {
				// Limit is not reached yet so just add to text
				if ($this->current < $this->limit) {
					$this->text .= $char;
					// Having an exit point when still in limit
					if ($char == '>') {
						$in_tag = FALSE;
					}
				}
				else if ($char == ' ' || $char == "\n") {
					// Space or new line. Hand differently based on if its pre tag or after tag
					if (strlen($tag) > 0) {
						// First whitespace, stop building tag
						$build_tag = FALSE;
					}
				}
				else if ($char == '/') {
					// Checking if this is before or after the tag
					if (strlen($tag) > 0) {
						// This is a self closing tag, it can be completely igonred
						$tag = '';
					} else {
						// This is a closing tag
						$close_tag = TRUE;
					}
				}
				else if ($char == '>') {
					// Closing of the tag
					if ($close_tag) {
						// This is a closing tag, need to test agains top of stack
						$last_tag = array_pop($this->stack);
						if ($last_tag == NULL) {
							// Array is empty, add to text as the open tag is in it
							$this->text .= '</' . $tag . '>';
						} else if ($last_tag != $tag) {
							// Closing tag for the not last tag
							// Probably bad html push last tag back and report error
							$this->log('Closing tag does not match last opened tag in stack.');
							array_push($this->stack, $last_tag);
						}
					} 
					else if (strlen($tag) > 0) {
						// Self closing tags will be ignored as $tag is empty string
						// Pushing the tag into the stack
						array_push($this->stack, $tag);
					}
					$in_tag = FALSE;
				}
				else if ($build_tag) {
					$tag .= $char;
				}
				else {
					// Regular char should not be reached
					$this->log("Error, this should never be reached\n");
				}
			}
			else if ($in_elem) {
				// Element. Adding to text but not to clean.
				$this->text .= $char;
				if ($char == ';') {
					$in_elem = FALSE;
				}
			}
			else if ($char == '<') {
				// Starting a tag
				$in_tag = TRUE;
				$tag = '';
				$build_tag = TRUE;
				$close_tag = FALSE;
				if ($this->current < $this->limit) {
					$this->text .= '<';
				}
			}
			else if ($char == '&' && ($this->current < $this->limit)) {
				// Only allow adding elements if not yet above the limit
				$in_elem = TRUE;
				$this->text .= '&';
				$this->current += 1; // Progressing size by one
			}
			else if ($this->current < $this->limit) { // Only reachable if both flags are false
				// If limit is not reached yet, add normal char
				$this->current += 1;
				$this->text .= $char;
			}
			// taking a step forward
			$this->walker += 1;
		}
	}
}

