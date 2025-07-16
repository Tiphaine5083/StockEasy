<?php
    /**
     * The autoloader automatically requires files when a class/namespace is used.
     */
     
    spl_autoload_register(function ($classname) {
        // $classname represents the class the program wants to instantiate, including its full namespace, e.g. "App\Models\User"
        // We replace the backslashes in the namespace with folder path slashes, resulting in: "App/Models/User"
        $path = str_replace('\\', '/', $classname);
    
        // We replace "App" with "src" in the path, resulting in: "src/Models/User"
        $path = str_replace('App', 'src', $path);
    
        // We add the .php extension, resulting in: "src/Models/User.php"
        $filename = __DIR__ . '/../../' . $path . '.php';
    
        // We include the file once if it exists
        if (file_exists($filename)) {
            require_once $filename;
        }
    });
