<?php
/*
    This file is part of DreamObjects, a plugin for WordPress.

    DreamObjects is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 2 of the License, or
    (at your option) any later version.

    DreamObjects is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with WordPress.  If not, see <http://www.gnu.org/licenses/>.

*/

if (!defined('ABSPATH')) {
    die();
}

class DHDOxZip {
    public function __construct() {}
    private function _rglobRead($source, &$array = array()) {
        if (!$source || trim($source) == "") {
            $source = ".";
        }
        foreach ((array) glob($source . "/*/") as $key => $value) {
            $this->_rglobRead(str_replace("//", "/", $value), $array);
        }
    
        foreach ((array) glob($source . "*.*") as $key => $value) {
            $array[] = str_replace("//", "/", $value);
        }
    }
    private function _zip($array, $part, $destination) {
        $zip = new ZipArchive;
        @mkdir($destination, 0777, true);
    
        if ($zip->open(str_replace("//", "/", "{$destination}/partz{$part}.zip"), ZipArchive::CREATE)) {
            foreach ((array) $array as $key => $value) {
                $zip->addFile($value, str_replace(array("../", "./"), NULL, $value));
            }
            $zip->close();
        }
    }
    public function zip($limit = 500, $source = NULL, $destination = "./") {
        if (!$destination || trim($destination) == "") {
            $destination = "./";
        }
    
        $this->_rglobRead($source, $input);
        $maxinput = count($input);
        $splitinto = (($maxinput / $limit) > round($maxinput / $limit, 0)) ? round($maxinput / $limit, 0) + 1 : round($maxinput / $limit, 0);
    
        for($i = 0; $i < $splitinto; $i ++) {
            $this->_zip(array_slice($input, ($i * $limit), $limit, true), $i, $destination);
        }
        
        unset($input);
        return;
    }
    public function unzip($source, $destination) {
        @mkdir($destination, 0777, true);
    
        foreach ((array) glob($source . "/*.zip") as $key => $value) {
            $zip = new ZipArchive;
            if ($zip->open(str_replace("//", "/", $value)) === true) {
                $zip->extractTo($destination);
                $zip->close();
            }
        }
    }
    
    public function __destruct() {}
}

//$zip = new DHDOxZip;
//$zip->zip(500, "images/", "images_zip/");
//$zip->unzip("images_zip/", "images/");