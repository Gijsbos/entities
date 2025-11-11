<?php
declare(strict_types=1);

namespace gijsbos\Entities\Utils;

use ReflectionClass;
use gijsbos\Entities\EntityClassReflection;
use gijsbos\Entities\EntityClassReflectionList;
use gijsbos\Logging\Classes\LogEnabledClass;

use function gijsbos\Logging\Library\log_info;

/**
 * EntityClassCache
 */
class EntityClassCache extends LogEnabledClass
{
    public static $CACHE_FOLDER = "cache/entities";

    /**
     * ENTITY_MAP
     */
    public static $ENTITY_MAP = null;

    /**
     * verifyCacheFileFolder
     */
    private function verifyCacheFileFolder(string $cacheFolder) : void
    {
        if(!is_dir($cacheFolder))
            mkdir($cacheFolder, 0777, true);
    }

    /**
     * getCacheFileFilePath
     */
    private function getCacheFileFilePath(string $className) : false | string
    {
        $cacheFolder = self::$CACHE_FOLDER;

        // Not found
        if($cacheFolder === false)
            return false;

        // Format cache folder path
        $cacheFolder = str_must_not_end_with($cacheFolder, "/");

        // Verify cache folder
        self::verifyCacheFileFolder($cacheFolder);

        // Read cacheFile
        $classFileName = str_replace("\\", "_", $className);

        // Get class file path
        $reflection = new ReflectionClass($className);

        // Get file
        $filePath = $reflection->getFileName();

        // Get hash
        $fileHash = hash_file("xxh3", $filePath);

        // Lookup file in cache
        return "$cacheFolder/$classFileName-$fileHash";
    }

    /**
     * storeInMemory
     */
    private function storeInMemory(EntityClassReflection $entityClassReflection)
    {
        if(EntityClassCache::$ENTITY_MAP === null)
            EntityClassCache::$ENTITY_MAP = [];

        // Store in cache
        EntityClassCache::$ENTITY_MAP[$entityClassReflection->getName()] = $entityClassReflection;
    }

    /**
     * parseEntityClassReflection
     */
    private function parseEntityClassReflection(string $className)
    {
        $entityClassReflectionClassName = $className::getEntityClassReflectionClassName();
        
        // Parse entity
        $entityClassReflection = $entityClassReflectionClassName::parseEntityClassReflection($className);
        
        // return value
        return $entityClassReflection;
    }

    /**
     * readEntityClassReflectionFromCacheFile
     */
    public function readEntityClassReflectionFromCacheFile(string $className) : false | EntityClassReflection
    {
        // Get path
        $cacheFilePath = $this->getCacheFileFilePath($className);

        // Not found in cache
        if($cacheFilePath === false || !is_file($cacheFilePath))
        {
            log_info("=> Reading cache file for '$className' failed, file '$cacheFilePath' not found");
            return false;
        }

        // Log
        log_info("=> Reading cache file for '$className' from cache file '$cacheFilePath'");

        // Get entity class reflection
        $entityClassReflectionClassName = $className::getEntityClassReflectionClassName();
        
        // Un serialise data
        $entityClassReflection = $entityClassReflectionClassName::unserialize(file_get_contents($cacheFilePath));

        // Store in memory
        $this->storeInMemory($entityClassReflection);

        // Return reflection
        return $entityClassReflection;
    }

    /**
     * storeEntityClassReflectionInCache
     */
    public function storeEntityClassReflectionInCache(EntityClassReflection $entityClassReflection)
    {
        log_info("=> Storing entity class reflection '".$entityClassReflection->getName()."' in cache");

        // Store
        $cacheFilePath = $this->getCacheFileFilePath($entityClassReflection->getName());

        // Create cache content
        $serializedEntityClassData = $entityClassReflection->serialize();

        // Store as file, could be skipped if the cache file path is false thus no cache folder is set
        if(is_string($cacheFilePath))
            file_put_contents($cacheFilePath, $serializedEntityClassData);

        // Store in memory
        $this->storeInMemory($entityClassReflection);
    }

    /**
     * createEntityClassReflectionCache
     */
    public function createEntityClassReflectionCacheFile(string $className) : EntityClassReflection
    {
        // Read file and set value in ENTITY_MAP memory
        $entityClassReflection = $this->parseEntityClassReflection($className);
        
        // Store in cache
        $this->storeEntityClassReflectionInCache($entityClassReflection);

        // Return reflection
        return $entityClassReflection;
    }

    /**
     * get
     */
    public function get(string $className, bool $useCache = true)
    {
        // Read from memory
        if(self::$ENTITY_MAP !== null && array_key_exists($className, self::$ENTITY_MAP))
            return self::$ENTITY_MAP[$className];

        // Read from cache
        if($useCache)
        {
            // From cache
            $entityClassReflection = $this->readEntityClassReflectionFromCacheFile($className);

            // Found
            if($entityClassReflection instanceof EntityClassReflection)
            {
                log_info("=> Get: retrieved cache file for '$className'");
                return $entityClassReflection;
            }
        }

        // Log
        log_info("=> Get: created cache file for '$className'");
        
        // Create cache file
        $entityClassReflection = $this->createEntityClassReflectionCacheFile($className);

        // Return file
        return $entityClassReflection;
    }

    /**
     * getEntityClassReflectionListFromCache
     */
    public function getEntityClassReflectionListFromCache() : EntityClassReflectionList
    {
        $cacheFolder = self::$CACHE_FOLDER;

        $entityClassReflectionList = new EntityClassReflectionList();

        if(is_dir($cacheFolder))
        {
            foreach(scandir($cacheFolder) as $item)
            {
                if($item !== "." && $item !== "..")
                {
                    $path = "$cacheFolder/$item";

                    $entityClassReflection = EntityClassReflection::unserialize(file_get_contents($path));

                    $entityClassReflectionList->add($entityClassReflection);
                }
            }
        }
        
        return $entityClassReflectionList;
    }
}