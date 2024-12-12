<?php if (!defined('PmWiki')) exit();

# using utf8 in php
# ------------------
# Strings in php are just bytes, php doesn't support utf-8 in the language itself, but has some helper functions.
# Php string is just a set of bytes where each byte is seen as a char with code in range 0-255.  
# So the programmer must after reading in a string from a file detect the encoding itself
# and encode it as utf8 if it was not that encoding already.  (use function mb_convert_encoding)
# Then the programmer must split the string in substrings where each string represents an utf8 character,
# so that we can work with utf8 characters. 
# An utf8 character can be one byte, but can also be multiple bytes. 
#
# The next question on stackoverflow explains how to best do this split  
# https://stackoverflow.com/questions/3666306/how-to-iterate-utf-8-string-in-php
#  question: how to iterate over utf8 string in php?
#  answer: 
#   1) pregsplit with following code will work because with 'u' flag it support
#   
#      example: https://www.php.net/manual/en/function.mb-split.php#99851
#        function mb_str_split( $string ) {
#          # Split at all position not after the start: ^
#          # and not before the end: $
#          return preg_split('/(?<!^)(?!$)/u', $string );
#          #                   ------~~~~~~~ 
#          #                     |     `-> if look forward at current position and see$  => no match
#          #                     `-> negative lookbehind => if look back at current position and see ^ => no match
#          #                   otherwise always match
#          #     so only split between chars inside string and not at its beginning and end! (prevents empty first and last entry in result array!)
#        }
#
#        It can be used like so
#
#            foreach(mb_str_split("Kąt") as $c) {
#                echo "{$c}\n";  # terminal supporting utf8
#            }
#
#   2) However pregsplit is quadratic slow for long strings, so instead use MbStrIterator (source below). 
#      It can be used like so
#
#            foreach(new MbStrIterator("Kąt") as $c) {
#                echo "{$c}\n";  # terminal supporting utf8
#            }

# Multi-Byte String(utf8) iterator class
class MbStrIterator implements Iterator
{
    private $iPos   = 0;
    private $iSize  = 0;
    private $sStr   = null;

    // Constructor
    public function __construct(/*string*/ $str)
    {
        // Save the string
        $this->sStr     = $str;

        // Calculate the size of the current character
        $this->calculateSize();
    }

    // Calculate size
    private function calculateSize(): void {

        // If we're done already
        if(!isset($this->sStr[$this->iPos])) {
            return;
        }

        // Get the character at the current position
        $iChar  = ord($this->sStr[$this->iPos]);

        // If it's a single byte, set it to one
        if($iChar < 128) {
            $this->iSize    = 1;
        }

        // Else, it's multi-byte
        else {

            // Figure out how long it is
            if($iChar < 224) {
                $this->iSize = 2;
            } else if($iChar < 240){
                $this->iSize = 3;
            } else if($iChar < 248){
                $this->iSize = 4;
            } else if($iChar == 252){
                $this->iSize = 5;
            } else {
                $this->iSize = 6;
            }
        }
    }

    // Current
    public function current(): string {

        // If we're done
        if(!isset($this->sStr[$this->iPos])) {
            return false;
        }

        // Else if we have one byte
        else if($this->iSize == 1) {
            return $this->sStr[$this->iPos];
        }

        // Else, it's multi-byte
        else {
            return substr($this->sStr, $this->iPos, $this->iSize);
        }
    }

    // Key
    public function key(): int
    {
        // Return the current position
        return $this->iPos;
    }

    // Next
    public function next(): void
    {
        // Increment the position by the current size and then recalculate
        $this->iPos += $this->iSize;
        $this->calculateSize();
    }

    // Rewind
    public function rewind(): void
    {
        // Reset the position and size
        $this->iPos     = 0;
        $this->calculateSize();
    }

    // Valid
    public function valid(): bool
    {
        // Return if the current position is valid
        return isset($this->sStr[$this->iPos]);
    }
}