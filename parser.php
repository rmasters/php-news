<?php

require __DIR__ . "/includes/Releases.php";
require __DIR__ . "/includes/Release.php";
require __DIR__ . "/includes/Change.php";

$path = "./NEWS.4";

$file = file_get_contents($path);
$lines = explode("\n", $file);

$entries = array();

/**
 * Parsing notes, names in braces are variable
 *
 * File starts with:
 *   PHP (whitespace) NEWS
 * or for PHP 4:
 *   PHP{MAJOR VERSION} (whitespace) NEWS
 * Then a repeated amount of braces to match the width of the file
 * Then each section header (using date() notation):
 *   d M Y, PHP{VERSION}
 * Where {VERSION} is a full release version, e.g. 5.3.7
 * To note: if the release is a future one, it's day, month and/or year will
 *  be replaced by question marks (?? ??? 2011 - assume ?? for day, ??? for 
 *  month and ???? for year (unlikely).
 * Sections contents are loosely syntaxed and may be difficult to parse, examples:
 *   - Zend Engine: {this colon is unreliable and not used on a few occasions}
 *     . Fixed bug #54585 (track_errors causes segfault). (Dmitry)
 *   - Upgraded bundled Sqlite3 to version 3.7.4. (Ilia)
 * These sub-sections only occur recently (5.2.7), the rest (PHP5 and 4) are
 *  like the second example.
 * 
 * In many cases these files are huge(!) - as of 7/5/2011 5.3's NEWS is 5999
 *  lines and 4.4's tops out at 3955.
 */

for ($i = 0; $i < count($lines); $i++) {
    if (strlen(trim($lines[$i])) == 0) {
        unset($lines[$i]);
    }
}
$lines = array_slice($lines, 0);
 
$releases = new Releases;
$curRelease = null;
$lastChange = null;
$curSection = null;
for ($i = 0; $i < count($lines); $i++) {
    if ($i < 2) continue;
    
    $releaseRegex = "/(?P<day>[0-9\?]{2}) (?P<month>[A-Za-z\?]{3}) (?P<year>[0-9\?]{4}), (Version|PHP) (?P<version>[0-9\.]+)/";
    if (preg_match($releaseRegex, $lines[$i], $releaseMatches) === 1) {
        // This is a release header
        $release = new Release;
        
        // Build date
        $months = array("Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec");
        $date = mktime(0, 0, 0,
            ($releaseMatches["month"] == "???") ? null : array_search($releaseMatches["month"], $months) + 1,
            ($releaseMatches["day"] == "??") ? null : $releaseMatches["day"],
            ($releaseMatches["year"] == "????") ? null : $releaseMatches["year"]);
        $release->date = $date;
        
        // Build version
        $release->version = $releaseMatches["version"];
        
        $releases->append($release);
        $curRelease = count($releases)-1;
    } else {
        // This is a change
        if (substr(trim($lines[$i]), 0, 1) !== "-") {
            // If the change doesn't begin with a dash, it continues from the previous change
            if (substr(trim($lines[$i]), 0, 1) == ".") {
                // If the line begins with " . " it is a sub item of the previous item (since 5.2.7)
                if (is_null($curSection)) {
                    $curSection = $releases[$curRelease]->changes[$lastChange]->getChange();
                    $curSection = trim($curSection, " -:");
                    if (!array_key_exists($curSection, $releases[$curRelease]->changes)) {
                        unset($releases[$curRelease]->changes[$lastChange]);
                        $releases[$curRelease]->changes[$curSection] = array();
                    }
                }
                
                $releases[$curRelease]->changes[$curSection][] = new Change($lines[$i]);
                $lastChange = count($releases[$curRelease]->changes[$curSection]) - 1;
            } else {
                if (!is_null($curSection)) {
                    $releases[$curRelease]->changes[$curSection][$lastChange]->appendChange($lines[$i]);
                } else {
                    $releases[$curRelease]->changes[$lastChange]->appendChange($lines[$i]);
                }
            }
        } else {
            // A new change
            $curSection = null;
            
            $releases[$curRelease]->changes[] = new Change($lines[$i]);
            $lastChange = count($releases[$curRelease]->changes) - 1;
        }
    }
}

// Save serialized content
file_put_contents("./NEWS.serialized", serialize($releases));