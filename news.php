<?php
/**
 * php-news-parser
 * 
 * Reads the NEWS file supplied with each release of the PHP source and
 * provides a readable, hyperlink-enriched HTML page.
 * 
 * @author Ross Masters <ross@php.net>
 * @version 2.0
 */

// Determine if the script is being called from the terminal or a webserver
define('CLI', php_sapi_name() == 'cli');

// If no file was supplied as an argument ($argv[1] or $_GET['file']) then look
// for a file named NEWS in the same directory.
if (isset($argv) && count($argv) >= 2) {
    $path = $argv[1];
} else if (isset($_GET['file'])) {
    // @todo Check for directory traversal, a whitelist of files etc.
    $path = __DIR__ . "/NEWS";
} else {
    $path = __DIR__ . "/NEWS";
}

if (!is_readable($path)) {
    show_error("Could not find or open a/the news file.");
}

$contents = file_get_contents($path);
$lines = explode("\n", $contents);

// Strip out empty lines and reset the list indices
for ($i = 0; $i < count($lines); $i++) {
    $lines[$i] = trim($lines[$i]);
    if (strlen($lines[$i]) == 0) {
        unset($lines[$i]);
    }
}
$lines = array_slice($lines, 0);

// Begin parsing
$releases = array();
$curRelease = null;
// References to the last item and the current section
$lastChange = null;
$curSection = null;

$months = array("Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dev");

for ($i = 2; $i < count($lines); $i++) {
    $line = $lines[$i];

    // Regex matching the date and release version
    $releaseRegex = "/(?P<day>[0-9\?]{2}) (?P<month>[A-Za-z\?]{3}) (?P<year>[0-9\?]{4}), (Version|PHP) (?P<version>[0-9\.]+)/";

    // If this is a new release, set it up in $releases
    if (preg_match($releaseRegex, $line, $releaseMatches) === 1) {
        // Construct the date
        $date = mktime(0, 0, 0, 
            ($releaseMatches["month"] == "???") ? null : array_search($releaseMatches["month"], $months) + 1,
            ($releaseMatches["day"] == "??") ? null : $releaseMatches["day"],
            ($releaseMatches["year"] == "????") ? null : $releaseMatches["year"]);

        // Update the release version and create the new array
        $curRelease = $releaseMatches["version"];
        $releases[$curRelease] = array(
            "release_date" => $date,
            "changes" => array()
        );

        unset($lastChange);
        unset($curSection);
    } else {
        // Otherwise, this is a change

        // If the line begins with a dash it is either an individual change or a section
        // (If the next line begins with a period the current line is a section)

        $lead = substr($line, 0, 1);

        if ($curRelease == '5.3.0') {
            var_dump($line);
        }

        if ($lead == '-') {
            $line = ltrim($line, '- ');
            // Line is a new change or section
            if (substr($lines[$i+1], 0, 1) == '.') {
                // This line is a new section
                $releases[$curRelease]['changes'][$line] = array();
                // Update currentSection reference
                $curSection =& $releases[$curRelease]['changes'][$line];
                unset($lastChange);
            } else {
                // This line is a change
                $releases[$curRelease]['changes'][] = $line;
                // And update the lastChange reference
                $lastChange =& $releases[$curRelease]['changes'][count($releases[$curRelease]['changes'])-1];
            }
        } else if ($lead == '.') {
            // Line is a change in the current section
            // Use the changes list if the reference isn't set
            if (!isset($curSection)) {
                $curSection =& $releases[$curRelease]['changes'];
            }

            $curSection[] = ltrim($line, '. ');
            $lastChange =& $curSection[count($curSection)-1];
        } else {
            // Line is a continuation of the previous change
            // Add a new change if the reference isn't set
            if (!is_null($lastChange)) {
                var_dump("NO LAST CHANGE: $line");
            } else {
                $lastChange .= ' ' . $line;
            }
        }
    }
}

/**
 * Build the HTML output
 */
echo <<<HTML
<!doctype html>
<html>
<head>
<title>PHP Changelog</title>
<style>
    body {
        font: 1.00em Arial, Helvetica, sans-serif;
    }
    header {
        border-bottom: 7px solid #99c;
    }

    .released {
        color: #666;
    }
</style>
</head>
<body>
<header>
<h1>PHP Changelog</h1>
</header>
HTML;

$enrich = function($line) {
    // Link to people.php.net profiles
    // @todo Use http://people.php.net/js/userlisting.php to find correct username
    $line = preg_replace('/\(([A-Za-z_]+)\)/', '(<a href="http://people.php.net/user.php?username=$1">$1</a>)', $line);

    // Link to bug references
    $line = preg_replace('/#([0-9]+)/', '<a href="http://bugs.php.net/bug.php?id=$1">#$1</a>', $line);

    // Link to function docs
    $line = preg_replace('/([A-Za-z_]+)\(\)/', '<a href="http://php.net/$1">$1()</a>', $line);

    return $line;
};

foreach ($releases as $version => $rel) {
    echo '<h2><a name="php-' . $version . '">' . $version . '</a> <span class="released">' . date("jS M Y", $rel["release_date"]) . '</span></h2>';
    $print_changes = function($array, $section=null) use (&$print_changes, $enrich) {
        if (!is_null($section)) {
            echo '<li><strong>' . $section . '</strong>';
        }
        echo '<ul>';

        foreach ($array as $k => $change) {
            if (is_array($change)) {
                $print_changes($change, $k);
            } else {
                echo "<li>" . $enrich($change) . "</li>";
            }
        }

        echo '</ul>';
        if (!is_null($section)) {
            echo '</li>';
        }
    };
    $print_changes($rel['changes']);
}
echo <<<HTML
</body>
</html>
HTML;

// Show an error message
function show_error($message) {
    if (CLI) {
        $stderr = fopen('php://stderr', 'w+');
        fwrite($stderr, $message . "\n");
        fclose($stderr);
    } else {
        echo "<h1>Error</h1><p>" . $message . "</p>";
    }
    exit(1);
}
