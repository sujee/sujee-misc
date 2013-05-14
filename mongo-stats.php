<?php
/*
 * Script to print out some mongodb stats;
 * collections are printed out in the order of thier size (large to small).
 * Also prints out indexes by their size.
 *
 * usage: php mongo-stats.php  <db name>
 *
 * needs php mongo client (sudo pecl install mongo)
 */


function sortBySize($a, $b) {
    return $b['size'] - $a['size'];
}


// prints number in human readable format (2.1 MB,  4.5 GB)
function humanFileSize($size, $precision = 1, $show = "")
{
    $b = $size;
    $kb = round($size / 1024, $precision);
    $mb = round($kb / 1024, $precision);
    $gb = round($mb / 1024, $precision);

    if($kb == 0 || $show == "B") {
        return $b . " bytes";
    } else if($mb == 0 || $show == "KB") {
        return $kb . " KB";
    } else if($gb == 0 || $show == "MB") {
        return $mb . " MB";
    } else {
        return $gb . " GB";
    }
}

// prints human friendly number (e.g. 1.5 million)
function humanCount($size, $precision = 1)
{
    $b = $size;
    $thousand = round($size / 1000, $precision);
    $million = round($thousand / 1000, $precision);
    $billion = round($million / 1000, $precision);

    if($thousand == 0 ) {
        return $b . " ";
    } else if($million == 0) {
        return $thousand . " thousand";
    } else if($billion == 0) {
        return $million . " million";
    } else {
        return $billion . " billion";
    }
}


// MAIN

if (count($argv) != 2)
{
    print "usage : php $argv[0]  <db name>\n";
    exit(1);
}

$dbname = $argv[1];
$m = new MongoClient();
$db = $m->$dbname;

$results = $db->command(array('dbStats' => 1));
print "db : " . $dbname . "\n";
print "   size=" . humanFileSize($results['storageSize']) . "\n";

$collections = array ();

foreach ($db->getCollectionNames() as $cname)
{
    $collection = $db->selectCollection($cname);
    //print $collection->getName() . "  " . $collection->count() . "\n" ;

    $results = $db->command(array( 'collStats' => $cname ));
    //var_dump($results);
    $c = array ();
    $c['name'] = $results['ns'];
    $c['count'] = $results['count'];
    $c['size'] = $results['size'];
    $c['total_index_size'] = $results['totalIndexSize'];

    $indexes = array ();
    foreach ($results['indexSizes'] as $k => $v)
    {
        $idx = array ();
        $idx['name'] = $k;
        $idx['size'] = $v;
        $indexes[] = $idx;
    }
    usort($indexes, 'sortBySize');
    $c['indexes'] = $indexes;
    $collections[] = $c;
}

usort($collections, 'sortBySize');

echo "------- collections by size -------\n";
foreach ($collections as $c)
{
    print $c['name'] . ",  count=" . humanCount($c['count']) .  ",  size=" . humanFileSize($c['size']) .  "\n";
    print "   indexes. total_size=" . humanFileSize($c['total_index_size']) . "\n";
    foreach ($c['indexes'] as $idx)
    {
        print "      " . $idx['name']  . " = "  . humanFileSize($idx['size']) . "\n";
    }
    print "\n";
}
?>
