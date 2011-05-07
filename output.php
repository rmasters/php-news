<?php
/**
 * Present a neatly formatted page of NEWS entries from a pre-parsed file
 */
require __DIR__ . "/includes/Releases.php";
require __DIR__ . "/includes/Release.php";
require __DIR__ . "/includes/Change.php";

$releases = new Releases;
$releases->unserialize(file_get_contents("./NEWS.serialized"));

?>
<!doctype html>
<html>
<head>
    <title>PHP Changelog</title>
    <link rel="stylesheet" type="text/css" href="static/news.css">
</head>
<body>
    <header id="page-header">
        <h1 id="page-title">PHP News</h1>
    </header>
    <nav id="version-toc">
<?php foreach ($releases->keys() as $version): ?>
        <a href="#php-<?php echo str_replace(".", "-", $version) ?>"><?php echo $version ?></a>
<?php endforeach; ?>
    </nav>
    <p>Generated <?php echo date("r", filemtime("./NEWS.serialized")) ?></p>
<?php foreach ($releases as $version => $release): ?>
    <section id="php-<?php echo str_replace(".", "-", $version) ?>" class="release">
        <header><h1>PHP <?php echo $version ?> - released <?php echo date("jS M, Y", $release->date) ?></h1></header>
        <ol class="changes">
    <?php foreach ($release->changes as $section => $change): ?>
        <?php if (is_array($change)): ?>
            <h2><?php echo $section ?></h2>
            <ol>
            <?php foreach ($change as $c): ?>
                <li><?php echo $c->getFormattedChange() ?></li>
            <?php endforeach; ?>
            </ol>
        <?php else :?>
        <li><?php echo $change->getFormattedChange() ?></li>
        <?php endif; ?>
    <?php endforeach; ?>
        </ol>
    </section>
<?php endforeach; ?>
    <footer id="page-footer">
        <p>Powered by <a href="http://github.com/rmasters/php-news">php-news</a></p>
    </footer>
</body>
</html>