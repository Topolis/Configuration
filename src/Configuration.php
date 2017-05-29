<?php

namespace Topolis\Configuration;

use ArrayAccess;
use Symfony\Component\Yaml\Yaml;
use Topolis\FunctionLibrary\Collection;

class Configuration implements ArrayAccess {

    protected $data = [];
    protected $files = [];
    protected $config = [];

    const NOTFOUND = "config:value:notfound";

    public static $defaults = [
        "path-config" => "../app/config",
        "path-cache" => "../app/cache",
        "config-files" => ["config.yml", "config_local.yml"],
        "cache-key" => "config",
        "use-environment" => false,
    ];

    const PATH_SEPERATOR = ".";
    const CACHE_FILE = "config.cache";

    public function __construct($config){

        $this->config = $config + self::$defaults;

        // Determine list of config files
        $this->files = $this->config["config-files"];

        if(!is_array($this->files))
            $this->files = [$this->files];

        if($this->config["use-environment"] && isset($_SERVER[$this->config["use-environment"]]))
            $this->files[] = $_SERVER[$this->config["use-environment"]];

        // Try cached or load from yaml
        if($this->config["path-cache"])
            $this->data = $this->getCached();

        // Global config
        if(!$this->data){
            $this->data = $this->getStored();
            $this->setCached($this->data);
        }
    }

    protected function getStored(){
        $configFolder = $this->config["path-config"];
        $config = [];

        foreach(array_reverse($this->files) as $file){
            $data = Yaml::parse( file_get_contents($configFolder."/".$file) );

            if($data)
                Collection::unionTree($config, $data);
        }

        return $config;
    }

    protected function getCached(){

        $cacheFolder = $this->config["path-cache"];
        $configFolder = $this->config["path-config"];
        $cacheFile  = $cacheFolder."/".$this->config["cache-key"].".cache";

        // No cache folder configured
        if(!$cacheFolder)
            return false;

        // Collect files for cache validity checks
        $configAge = 0;
        foreach($this->files as $file){
            $time = @filemtime($configFolder."/".$file);
            $configAge = max($configAge, $time);
        }

        $cacheAge = @filemtime($cacheFile);

        // No cache present
        if($cacheAge === false)
            return false;
        // Cache to old
        if($cacheAge < $configAge){
            unlink($cacheFile);
            return false;
        }

        // Cache is ok
        $config = file_get_contents($cacheFile);
        return unserialize($config);
    }

    protected function setCached($config){

        $cacheFolder = $this->config["path-cache"];
        $cacheFile   = $cacheFolder."/".$this->config["cache-key"].".cache";

        $config = serialize($config);
        file_put_contents($cacheFile, $config);
    }

    public function get($path, $default = null){
        return Collection::get($this->data, $path, $default);
    }

    // Array Interface
    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    public function offsetExists($offset) {
        return isset($this->data[$offset]);
    }

    public function offsetUnset($offset) {
        unset($this->data[$offset]);
    }

    public function offsetGet($offset) {
        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }
}