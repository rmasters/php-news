<?php

class Change
{
    private $change;
    
    public function __construct($change = null) {
        $this->setChange($change);
    }
    
    public function setChange($change) {
        $change = ltrim($change, "-. ");
        $change = rtrim($change, ": ");
        $this->change = $change;
    }
    
    public function appendChange($changeExtra) {
        $changeExtra = trim($changeExtra, ": ");
        $this->change = $this->change . " " . $changeExtra;
    }
    
    public function getChange() {
        return $this->change;
    }
    
    public function getFormattedChange() {
        $change = $this->change;
        
        // Replace usernames with people.php.net links
        // TODO: use http://people.php.net/js/userlisting.php to find correct username
        if (strrpos($change, "(")) {
            $match = substr($change, strrpos($change, "("));
            $change = str_replace($match,
                preg_replace("/\(([A-Za-z_]+)\)/", "(<a href=\"http://people.php.net/user.php?username=$1\">$1</a>)", $match),
                $change);
        }
        
        // Replace bug references
        $change = preg_replace("/#([0-9]+)/", 
            "<a href=\"http://bugs.php.net/bug.php?id=$1\">#$1</a>", $change);
        
        // Replace function references
        $change = preg_replace("/ ([A-Za-z_]+)\(\)/", " <a href=\"http://php.net/$1\">$1()</a>", $change);
        
        return $change;
    }
}