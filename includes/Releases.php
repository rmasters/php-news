<?php

class Releases extends ArrayIterator implements Serializable
{
    private $releases = array();
    private $position = 0;
    
    public function __construct(array $releases = null) {
        if (is_array($releases)) {
            foreach ($releases as $release) {
                $this[] = $release;
            }
        }
    }
    
    // TODO: why doesn't $Releases[] trigger this?
    public function append($release) {
        $this->releases[] = $release;
    }
    
    public function asort() {
        asort($this->releases);
    }
    
    public function count() {
        return count($this->releases);
    }
    
    public function current() {
        return $this->releases[$this->position];
    }
    
    public function getArrayCopy() {
        return $this->releases;
    }
    
    public function getFlags() {}
    
    public function key() {
        return $this->releases[$this->position]->version;
    }
    
    // TODO: sort by release
    public function ksort() {
        ksort($this->releases);
    }
    
    public function natcasesort() {
        natcasesort($this->releases);
    }
    
    public function natsort() {
        natsort($this->releases);
    }
    
    public function next() {
        $this->position++;
    }
    
    public function offsetExists($index) {
        return isset($this->releases[$index]);
    }
    
    public function offsetGet($index) {
        return $this->releases[$index];
    }
    
    public function offsetSet($index, $release) {
        $this->releases[$index] = $release;
    }
    
    public function offsetUnset($index) {
        unset($this->releases[$index]);
    }
    
    public function rewind() {
        $this->position = 0;
    }
    
    public function seek($position) {
        $this->position = $position;
    }
    
    public function serialize() {
        return serialize($this->releases);
    }
    
    public function setFlags($flags) {}
    
    public function uasort($cmp_func) {
        uasort($this->releases);
    }
    
    public function uksort($cmp_func) {
        uksort($this->releases);
    }
    
    public function unserialize($serialized) {
        $this->releases = unserialize($serialized);
    }
    
    public function valid() {
        return isset($this->releases[$this->position]);
    }
    
    public function keys() {
        $keys = array();
        foreach ($this->releases as $release) {
            $keys[] = $release->version;
        }
        return $keys;
    }
}