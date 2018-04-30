<?php 

require_once("Epub/epub.php");
use Epub\Epub;

$book = [
    'meta' => [
        'title'         => "comicTest",
        'creator'       => "walker",
        'publisher'     => "",
        'date'          => "",
        'identifier'    => "1234567890",
        'language'      => "",
    ],
    'pirctures' => [],
    'folder' => ""
];

$epub = new Epub;
$epub->buildProject($book);
