<?php

namespace EPub;

use \DOMDocument;

class EPub
{
    protected $project, $path, $resources, $pictures;

    public $meta = [
        'title'         => "",
        'creator'       => "",
        'publisher'     => "",
        'date'          => "",
        'identifier'    => "",
        'language'      => ""
    ];

    public function buildProject($book)
    {
        $this->project = $book['meta']['title'];
        $this->path = [
            'root'      => realpath($_SERVER['DOCUMENT_ROOT'])."/",
            'import'    => realpath($_SERVER['DOCUMENT_ROOT'])."/import",
            'system'    => realpath($_SERVER['DOCUMENT_ROOT'])."/system"."/",
            'tmp'       => realpath($_SERVER['DOCUMENT_ROOT'])."/system/tmp"."/",
            'mimetype'  => realpath($_SERVER['DOCUMENT_ROOT'])."/system/mimetype.zip",
            'project'   => realpath($_SERVER['DOCUMENT_ROOT'])."/system/tmp/".$this->project."/",
        ];
        $this->resources = [];
        $this->pictures = [];
        
        if(!file_exists($this->path['project'] . "META-INF/")){
            mkdir($this->path['project'] . "META-INF", 0755, true);
        }
        
        if(!file_exists($this->path['project'] . "OEBPS/")){
            mkdir($this->path['project'] . "OEBPS", 0755, true);
        }

        $this->setMeta($book['meta']);

        $pictures = $this->getPictures();
        $this->createXHTML($pictures);
        $this->createOPF($pictures);
        $this->createNCX($pictures);

        $this->createContainer();
        $this->createIBooks();
        
        $this->export();
    }

    protected function setMeta($meta)
    {
        $this->meta['title']        = $meta['title'];
        $this->meta['creator']      = $meta['creator'];
        $this->meta['publisher']    = $meta['publisher'];
        $this->meta['date']         = $meta['date'];
        $this->meta['identifier']   = $meta['identifier'];
        $this->meta['language']     = $meta['language'];
    }
    
    protected function createContainer()
    {
        $xmlstr =   '<container version="1.0" xmlns="urn:oasis:names:tc:opendocument:xmlns:container">'.PHP_EOL;
        $xmlstr .=  '   <rootfiles>'.PHP_EOL;
        $xmlstr .=  '       <rootfile full-path="OEBPS/content.opf" media-type="application/oebps-package+xml" />'.PHP_EOL;
        $xmlstr .=  '   </rootfiles>'.PHP_EOL;
        $xmlstr .=  '</container>'.PHP_EOL;

        $xml = new DOMDocument('1.0');
        $xml->loadXML($xmlstr);
        $xml->encoding = 'UTF-8';
        $xml->save($this->path['project'] . "META-INF/container.xml");
    }
    
    protected function createOPF($pictures)
    {
        $xmlstr =   '<package xmlns="http://www.idpf.org/2007/opf" xmlns:dc="http://purl.org/dc/elements/1.1/" unique-identifier="bookid" version="2.0">'.PHP_EOL;
        $xmlstr .=  '   <metadata>'.PHP_EOL;
        $xmlstr .=  '       <dc:title>'. $this->meta['title'] .'</dc:title>'.PHP_EOL;
        $xmlstr .=  '       <dc:creator>'. $this->meta['creator'] .'</dc:creator>'.PHP_EOL;
        $xmlstr .=  '       <dc:publisher>'. $this->meta['publisher'] .'</dc:publisher>'.PHP_EOL;
        $xmlstr .=  '       <dc:date>'. $this->meta['date'] .'</dc:date>'.PHP_EOL;
        $xmlstr .=  '       <dc:identifier id="bookid">'. $this->meta['identifier'] .'</dc:identifier>'.PHP_EOL;
        $xmlstr .=  '       <dc:language>'. $this->meta['language'] .'</dc:language>'.PHP_EOL;
        $xmlstr .=  '       <meta property="rendition:layout">pre-paginated</meta>'.PHP_EOL;
        $xmlstr .=  '       <meta property="rendition:orientation">auto</meta>'.PHP_EOL;
        $xmlstr .=  '       <meta property="rendition:spread">landscape</meta>'.PHP_EOL;
        $xmlstr .=  '   </metadata>'.PHP_EOL;
        $xmlstr .=  '   <manifest>'.PHP_EOL;
        $xmlstr .=  '       <item id="ncx" href="toc.ncx" media-type="application/x-dtbncx+xml"/>'.PHP_EOL;

        $resources = $this->getResources("OEBPS");
        // add resource items
        foreach($resources as $resource){
        $xmlstr .=  '       <item id="' . $resource['id'] . '" href="' . $resource['href'] . '" media-type="' . $resource['type'] . '"/>'.PHP_EOL;
        }
        $xmlstr .=  '   </manifest>'.PHP_EOL;
        $xmlstr .=  '   <spine toc="ncx" page-progression-direction="rtl">'.PHP_EOL;
        // add spine items
        foreach($pictures as $picture){
        $xmlstr .=  '       <itemref idref="' . $picture['id']. ".xhtml" . '" />'.PHP_EOL;
        }
        $xmlstr .=  '   </spine>'.PHP_EOL;
        $xmlstr .=  '   <guide>'.PHP_EOL;
        // add reference items
        foreach($pictures as $picture){
            // $xmlstr .=  '       <reference href="' . $this->resources[$article['id']]['href'] . '" />'.PHP_EOL;
        }
        $xmlstr .=  '   </guide>'.PHP_EOL;
        $xmlstr .=  '</package>'.PHP_EOL;

        $xml = new DOMDocument('1.0');
        $xml->loadXML($xmlstr);
        $xml->encoding = 'UTF-8';
        $xml->save($this->path['project'] . "OEBPS/content.opf");
    }
    
    protected function createNCX($pictures)
    {
        $xmlstr =   '<!DOCTYPE ncx PUBLIC "-//NISO//DTD ncx 2005-1//EN" "http://www.daisy.org/z3986/2005/ncx-2005-1.dtd">'.PHP_EOL;
        $xmlstr .=  '<ncx xmlns="http://www.daisy.org/z3986/2005/ncx/" version="2005-1">'.PHP_EOL;
        $xmlstr .=  '   <head>'.PHP_EOL;
        $xmlstr .=  '       <meta name="dtb:uid" content="' . $this->meta['identifier'] . '"/>'.PHP_EOL;
        $xmlstr .=  '       <meta name="dtb:depth" content="1"/>'.PHP_EOL;
        $xmlstr .=   '       <meta name="dtb:totalPageCount" content="0"/>'.PHP_EOL;
        $xmlstr .=  '       <meta name="dtb:maxPageNumber" content="0"/>'.PHP_EOL;
        $xmlstr .=  '   </head>'.PHP_EOL;
        $xmlstr .=  '   <docTitle>'.PHP_EOL;
        $xmlstr .=  '       <text>' . $this->meta['title'] . '</text>'.PHP_EOL;
        $xmlstr .=  '   </docTitle>'.PHP_EOL;
        $xmlstr .=  '   <navMap>'.PHP_EOL;
        // add navPoint items
        foreach($pictures as $index => $picture){
        ++$index;
        $xmlstr .=  '       <pageTarget type="normal" value="' . $index . '">'.PHP_EOL;
        $xmlstr .=  '           <navLabel><text>' . $index . '</text></navLabel>'.PHP_EOL;
        $xmlstr .=  '           <content src="' . $this->resources[$picture['id'].".xhtml"]['id'] . '"/>'.PHP_EOL;
        $xmlstr .=  '       </pageTarget>'.PHP_EOL;
        }
        $xmlstr .=  '   </navMap>'.PHP_EOL;
        $xmlstr .=  '</ncx>'.PHP_EOL;

        $xml = new DOMDocument('1.0');
        $xml->loadXML(mb_convert_encoding($xmlstr, 'UTF-8'));
        $xml->encoding = 'UTF-8';
        $xml->save($this->path['project'] . "OEBPS/toc.ncx");
    }
    
    protected function createXHTML($pictures, $width = 1024, $height = 2055)
    {
        foreach($pictures as $picture){

        $size = getimagesize($this->path['project']."OEBPS/image/".$picture['src']);

        $xmlstr =   '<html xmlns="http://www.w3.org/1999/xhtml" encoding="UTF-8">'.PHP_EOL;
        $xmlstr .=  '     <head>'.PHP_EOL;
        $xmlstr .=  '         <title>' . $picture['id'] . '</title>'.PHP_EOL;
        $xmlstr .=  '         <meta name="viewport" content="width='.$size[0].', height='.$size[1].'"/>'.PHP_EOL;
        $xmlstr .=  '     </head>'.PHP_EOL;
        $xmlstr .=  '     <body>'.PHP_EOL;
        $xmlstr .=  '           <img src="'."image/".$picture['src'].'"/>'.PHP_EOL;
        $xmlstr .=  '     </body>'.PHP_EOL;
        $xmlstr .=  ' </html>';

        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->loadXML($xmlstr);
        $xml->encoding = 'UTF-8';
        $xml->save($this->path['project'] . "OEBPS/" . $picture['id'] . ".xhtml");
        }
    }
    
    protected function createIBooks()
    {
        $xmlstr =   '<display_options>'.PHP_EOL;
        $xmlstr .=  '    <platform name="*">'.PHP_EOL;
        $xmlstr .=  '        <option name="specified-fonts">false</option>'.PHP_EOL;
        $xmlstr .=  '        <option name="interactive">false</option>'.PHP_EOL;
        $xmlstr .=  '        <option name="fixed-layout">true</option>'.PHP_EOL;
        $xmlstr .=  '        <option name="open-to-spread">true</option>'.PHP_EOL;
        $xmlstr .=  '        <option name="orientation-lock">false</option>'.PHP_EOL;
        $xmlstr .=  '    </platform>'.PHP_EOL;
        $xmlstr .=  '</display_options>'.PHP_EOL;

        $xml = new DOMDocument('1.0');
        $xml->loadXML($xmlstr);
        $xml->encoding = 'UTF-8';
        $xml->save($this->path['project'] . "META-INF/com.apple.ibooks.display-options.xml");
    }

    protected function getPictures()
    {
        $this->scanPictures($this->path['import']);

        if(!file_exists($this->path['project']."OEBPS/image")){
            mkdir($this->path['project'] . "OEBPS/image", 0755, true);
        }

        $results = [];
        foreach($this->pictures as $path => $pictures){
            if($path != 'undefined' && !file_exists($this->path['project']."OEBPS/image/".$path)){
                mkdir($this->path['project'] . "OEBPS/image/".$path, 0755, true);
            }
            foreach($pictures as $picture){
                if( ! copy($this->path['import']."/".$picture, $this->path['project']."OEBPS/image/".$picture)){
                    die('image copy failed');
                }
                else{
                    $results[] = [
                        'id' => str_replace('/', '_', $picture),
                        'src' => $picture
                    ];
                }
            }
        }
        return $results;
    }

    protected function scanPictures($path)
    {
        $array = array_filter(scandir($path), function($item){
            return $item[0] != ".";
        });
        foreach($array as $key => $value){
            $extension = pathinfo($value, PATHINFO_EXTENSION);
            if(!$extension){
                $this->scanPictures($path."/".$value);
            }
            else{
                $relative_path = $path == $this->path['import'] ? "undefined" : substr($path, strlen($this->path['import']."/"));
                $file_path = $relative_path == 'undefined' ? "" : $relative_path."/";
                $this->pictures[$relative_path][] = $file_path.$value;
            }
        }
    }

    protected function getResources($directory = "")
    {
        $this->scanResources($this->path['project'].$directory, $directory == "");
        foreach($this->resources as $resource){
            $this->resources[$resource['id']]['href'] = substr($this->resources[$resource['id']]['href'], strlen($this->path['project'].$directory."/"));
        }
        return $this->resources;
    }

    protected function scanResources($path, $root = true)
    {
        if($root){
            $array = array_filter(scandir($path), function($item){
                return $item[0] != "." && $item != "mimetype";
            });
        }
        else{
            $array = array_filter(scandir($path), function($item){
                return $item[0] != "." && pathinfo($item, PATHINFO_EXTENSION) != "opf" && pathinfo($item, PATHINFO_EXTENSION) != "ncx";
            });
        }
        
        foreach($array as $key => $value){
            $extension = pathinfo($value, PATHINFO_EXTENSION);
            if(!$extension){
                $this->scanResources($path."/".$value, false);
            }
            else{
                $this->resources[$value] = [
                    'id' => $value,
                    'href' => $path."/".$value
                ];
                if(!$root){
                    $this->resources[$value]['type'] = $this->getMediaType($extension);
                }
            }
        }
    }

    protected function getMediaType($extension)
    {
        $content = file_get_contents( __DIR__ . "/media-type.json");
        $json = json_decode($content);
        return $json->$extension;
    }

    private function export()
    {
        $mimetype = $this->path['mimetype'];
        $file = $this->path['project'].$this->project . ".epub" ;

        if(is_file($this->path['mimetype']) && copy($this->path['mimetype'], $file)){

            exec( "cd " . $this->path['project'] . " && zip -9 -r " . $file . " */");
    
            header("Content-Description: File Transfer"); 
            header("Content-Type: application/octet-stream"); 
            header("Content-Disposition: attachment; filename='" . basename($file) . "'"); 
            readfile($file);
    
            exec( "cd " . $this->path['project'] . " && rm -rf *");
            rmdir($this->path['project']);
        }
        else{
            die("檔案遺失，請聯絡系統管理員");
        }
    }
}
