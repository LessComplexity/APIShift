<?php
/**
 * APIShift Engine v1.0.0
 * (c) 2020-present Sapir Shemer, DevShift (devshift.biz)
 * Released under the MIT License with the additions present in the LICENSE.md
 * file in the root folder of the APIShift Engine original release source-code
 * @author Sapir Shemer
 */

namespace APIShift\Core;

/**
 * This autoloader helps us avoid loading files in a hardcoded way, and loads them automatically Avoids re-loading files.
 * Based on the PSR-4 implementation (https://www.php-fig.org/psr/psr-4/)
 * This loader saves prefixes and their path data using a tree structure, smart, modular and can be extended
 */
class Autoloader {
    /**
     * A nested array with the prefixes and their parent folders
     * 
     * Each element represents a namespace and can be either a string representing the path/name of the folder or an array.
     * In case an element is an array the system will try to find the "path" key in the array to know the folder name.
     * If there is no "path" key present in an element the loader will not assign a folder name and will continue accordingly.
     * Each array element can have sub-elements which represent sub-namespaces and also can be either a string or an array.
     * A sub-element can have a boolean key "ignore" which means to ignore the previous folder and unset it - in other words
     * override the parent folder.
     */
    protected static $prefixes = [
        "APIShift" => [
            "path" => __DIR__ . "/..",
            "Extensions" => "../extensions"
        ]
    ];

    /**
     * Register loader with SPL autoloader stack.
     *
     * @return void
     */
    public static function register()
    {
        spl_autoload_register('APIShift\Core\Autoloader::loader');
    }

    /**
     * Adds a base directory for a namespace prefix.
     *
     * @param string $prefix The namespace prefix.
     * @param string $base_dir A base directory for class files in the namespace.
     * @param bool|null $ignore If true, then the loader will ignore the directory of the parent namespace
     * @return void
     */
    public static function addNamespace($prefix, $base_dir, bool $ignore = null)
    {
        // Collection of namespaces
        $prefixCollection = explode("\\", $prefix);
        // normalize the base directory with a trailing separator
        $base_dir = rtrim($base_dir, DIRECTORY_SEPARATOR);

        // Construct base element
        $prefixData = [];
        if($ignore != null)
        {
            $prefixData = [
                "path" => $base_dir,
                "ignore" => $ignore
            ];
        }
        else $prefixData = $base_dir;

        // Find existing parents in prefix tree
        $lastNamespaceParentkey = 0; // Stores the last parent key found
        $tempDependencyTreeIterator = &self::$prefixes;
        while(isset($tempDependencyTreeIterator[$prefixCollection[$lastNamespaceParentkey]]) && $lastNamespaceParentkey >= 0)
        {
            $tempDependencyTreeIterator = &$tempDependencyTreeIterator[$prefixCollection[$lastNamespaceParentkey]];
            $lastNamespaceParentkey++;
        }

        // Add new child elements from last existing parent
        while($lastNamespaceParentkey < count($prefixCollection))
        {
            $tempDependencyTreeIterator[$prefixCollection[$lastNamespaceParentkey]] = [];
            $tempDependencyTreeIterator = &$tempDependencyTreeIterator[$prefixCollection[$lastNamespaceParentkey]];
            $lastNamespaceParentkey++;
        }

        // Update last element
        if(is_array($prefixData) || (is_string($prefixData)
            && is_string($tempDependencyTreeIterator))) $tempDependencyTreeIterator = $prefixData;
        else $tempDependencyTreeIterator["path"] = $prefixData;
    }

    /**
     * Returns the path of the package selected
     * 
     * @param $package The package to get path from
     * @return void
     */
    protected static function getPathFromPackage($package) {
        // Get vendor parent directory
        if(is_array($package) && isset($package["path"])) return $package["path"];
        else if(is_string($package)) return $package;
        // Otherwise, if only array with children, delete the element
        else if(is_array($package) && count($package) > 0) return "";
    }

    /**
     * Loads the class by translating the prefixes into the containing folder and choosing from the folder
     * or src/test folders if present - priority by order (root folder, src folder then the test folder)
     * 
     * @param string $className The class name.
     * @return mixed The file name or false on failure.
     */
    public static function loader($className) {
        // No need to load exisitng classes
        if(class_exists($className) || interface_exists($className)) return true;

        $classPath = explode("\\", $className);
        // Stores the last key changed by the prefixes to avoid lower casing
        $lastKeyChanged = -1;
        // Stores the file key in case we unset paths due to prefix rules
        $fileKey = count($classPath) - 1;
        
        // Handle the vendor namespaces - avoids recursion even though we have a tree ;)
        if(isset(self::$prefixes[$classPath[0]])) {
            // Get vendor parent directory
            $vendorName = $classPath[0];
            $classPath[0] = self::getPathFromPackage(self::$prefixes[$classPath[0]]);
            if($classPath[0] == "") unset($classPath[0]);
            $lastKeyChanged = 0;

            // Get sub dependency directories
            $tempDependencyTree = &self::$prefixes[$vendorName];
            for($iter = 1; $iter < count($classPath); $iter++) {
                // When reaching a child containing only a path, break to loop
                if(is_string($tempDependencyTree) || (count($tempDependencyTree) == 1 && isset($tempDependencyTree["path"]))) break;
                // Assign the directory name
                else if(isset($tempDependencyTree[$classPath[$iter]])) {
                    // Ignore previous folder
                    if(isset($tempDependencyTree[$classPath[$iter]]["ignore"])
                    && $tempDependencyTree[$classPath[$iter]]["ignore"] == true) {
                        $prevPathIndex = $iter;
                        // Move back until last path to ignore
                        while($prevPathIndex > 0 && !isset($classPath[--$prevPathIndex]));
                        if(isset($classPath[$prevPathIndex])) unset($classPath[$prevPathIndex]);
                    }

                    // Change current folder name
                    $package = $classPath[$iter];
                    $classPath[$iter] = self::getPathFromPackage($tempDependencyTree[$classPath[$iter]]);
                    if($classPath[$iter] == "") unset($classPath[$iter]);
                    $lastKeyChanged = $iter;
                    // Move to next child
                    $tempDependencyTree = &$tempDependencyTree[$package];
                }
                // No child found
                else break;
            }
        }
        // External namespaces are located at the "../externals/vendor" folder
        else  $classPath[0] = __DIR__ . "../../externals/vendor/" . $classPath[0];

        // Make folders as lowercase
        for($iter = $lastKeyChanged + 1; $iter < $fileKey; $iter++) $classPath[$iter] = strtolower($classPath[$iter]);
        
        // Construct paths to find files - ordered by priority
        $pathToFindClass_regular = implode("/", $classPath) . ".php";
        $pathToFindClass_src = implode("/", array_splice($classPath, 0, count($classPath) - 1)) . "/src/" . end($classPath) . ".php";
        $pathToFindClass_test = implode("/", array_splice($classPath, 0, count($classPath) - 1)) . "/test/" . end($classPath) . ".php";
        $foundPath = "";

        // Load the files
        if(file_exists($pathToFindClass_regular)) $foundPath = $pathToFindClass_regular;
        else if(file_exists($pathToFindClass_src)) $foundPath = $pathToFindClass_src;
        else if(file_exists($pathToFindClass_test)) $foundPath = $pathToFindClass_test;
        // Move to other registered autoloaders
        else return false;

        // Require the class & return the name
        require $foundPath;
        return $foundPath;
    }
}

// Register the loader
Autoloader::register();

?>