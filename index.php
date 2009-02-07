<?php
/**
 * PHP news file reader
 * @author      Ross Masters <ross@php.net>
 * @license     http://opensource.org/licenses/gpl-3.0.html
 * @version     1.0
 */

// Set path to PHP news file here
define('NEWS_FILE', './news_example.txt');

/**
 * Parsing
 */

// Load and explode file into lines
$news = file_get_contents(NEWS_FILE);
$lines = explode("\n", $news);
// Create result array and link to it
$items = array();
$cur =& $items;

// Regex to split date and PHP version
$regex = '/([0-9]{2} [A-Za-z]{3} [0-9]{4}), PHP ([0-9]\.[0-9]\.[0-9]{1,3}?)/';
// Regex and replacement for bug references
$bugRegex = '/#([0-9]+)/';
$bugLink = '<a href="http://bugs.php.net/bug.php?id=$1">$0</a>';
// Regex and replacement for function references
$funcRegex = '/([A-Za-z_]+)\(\)/';
$funcLink = '<a href="http://www.php.net/$1">$0</a>';

// Loop through each line of the file contents
for ($i = 2; $i < count($lines); $i++) {
    if (!empty($lines[$i])) {
        // If this is an entry header (e.g. 08 Dec 2008, PHP 5.2.8
        if (preg_match($regex, $lines[$i], $match)) {
            $cur =& $items;
            $cur[$match[2]] = array();
            $cur =& $items[$match[2]];
            $cur['date'] = $match[1];
            $cur['items'] = array();
            continue;
        }
    
        // If this is an item
        if (substr($lines[$i], 0, 1) == '-') {
            $line = substr($lines[$i], 2);
            
            // Link bugs and functions
            $line = preg_replace($bugRegex, $bugLink, $line);
            $line = preg_replace($funcRegex, $funcLink, $line);
            
            $cur['items'][] = $line;
            continue;
        }
    
        // If this line continues from the previous item
        if (substr($lines[$i], 0, 1) == ' ') {
            // Pop the previous item off the array, append to it and put it back
            $last = array_pop($cur['items']);
            $last = trim($last) . ' ' . trim($lines[$i]);
            array_push($cur['items'], $last);
            continue;
        }
    }
}

/**
 * Output
 */

echo <<<HEAD
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
    <title>PHP News File</title>
    <meta http-equiv="content-type" content="text/html;charset=utf-8" />
    <meta http-equiv="Content-Style-Type" content="text/css" />
    <link rel="stylesheet" type="text/css" href="./news.css" />
    <link rel="shortcut icon" href="http://static.php.net/www.php.net/favicon.ico" /> 
</head>

<body>
    <h1>PHP News</h1>
    <ol class="toc">
        <li><strong><a name="toc">Version Contents:</a></strong></li>
HEAD;

foreach (array_keys($items) as $sect) {
    echo '<li><a href="#' . $sect . '">' . $sect . '</a></li>' . PHP_EOL;
}
echo '</ol>' . PHP_EOL;

foreach ($items as $v => $info) {
    echo '<h2><a name="' . $v . '">' . $v . ' - ' . $info['date'] . ' (' . count($info['items']) . ' items)</a></h2>' . PHP_EOL;
    echo '<ul>' . PHP_EOL;
    foreach ($info['items'] as $item) {
        echo '<li>' . $item . '</li>' . PHP_EOL;
    }
    echo '</ul>' . PHP_EOL;
    echo '<p class="contents"><a href="#toc">Back to contents</a></p>';
}

echo <<<FOOT
    <p class="footer">Powered by <a href="http://github.com/rmasters/php-news-parser/tree/master"><code>php-news-parser</code></a>.</p>
</body>
</html>
FOOT;
?>