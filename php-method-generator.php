<?php

if (count($_SERVER['argv'])==1)
{
  echo "

# Single file PHP 5.2 class style

php generate_methods.php lib/ServerGrove/Project/MyClass.php

# Single file PHP 5.3 class style with namespace

# Usage: php generate_methods.php file class

php generate_methods.php lib/ServerGrove/Project/MyClass.php 'ServerGrove\Project\MyClass'

# Directory with multiple files

# Usage: php generate_methods.php file namespace

php generate_methods.php lib/ServerGrove/Project 'ServerGrove\Project'


";

  die();
}

$path = $_SERVER['argv'][1];

$class = '';
if (!empty($_SERVER['argv'][2])) $class = $_SERVER['argv'][2];

function process($path, $class='')
{

  if (empty($class))
  {
    $class = str_replace('.php', '', basename($path));
  }

  echo "Processing $class @ $path\n";

  if (!file_exists($path)) {
    echo "Error: Path $path not found\n";
    return;
  }
  
  require $path;

  if (!class_exists($class)) {
    echo "Error: Class $class does not exist in $path\n";
    return;
  }

  $foo = new $class();

  $getTpl = '
  public function get%s()
  {
    return $this->%s;
  }
  ';

  $setTpl = '
  public function set%s($value)
  {
    $this->%s = $value;
  }
  ';

  $newcode = '';

  $reflect = new ReflectionClass($foo);
  $props   = $reflect->getProperties(ReflectionProperty::IS_PRIVATE | ReflectionProperty::IS_PROTECTED);
  $methods = $reflect->getMethods();
  foreach ($props as $prop)
  {
    $name = $prop->getName();

    $upName = ucfirst($name);
    $method = 'get'.$upName;

    try
    {
      $reflect->getMethod($method);
    }
    catch (ReflectionException $ex)
    {
      echo " Missing $method\n";

      $newcode .= sprintf($getTpl, $upName, $name);
    }

    $method = 'set'.$upName;

    try
    {
      $reflect->getMethod($method);
    }
    catch (ReflectionException $ex)
    {
      echo " Missing $method\n";

      $newcode .= sprintf($setTpl, $upName, $name)."\n";
    }
  }

  if (empty($newcode))
  {
    echo(" No new code to add to $class\n");
    return;
  }
  $code = file_get_contents($path);

  $pos = strrpos($code, '}');

  $code = substr($code, 0, $pos)."/* Added automatically ".date("Y-m-d")." */\n\n".$newcode."\n\n}\n";

  file_put_contents($path, $code);

}



if (!is_dir($path))
{
  process($path, $class);
}
else
{
  foreach (new DirectoryIterator($path) as $fileInfo)
  {
    if($fileInfo->isDir()) continue;

    $fname = $fileInfo->getFilename();

    if (strpos( $fname, '.php')===false) continue;

    $className = str_replace('.php', '', $fname);

    process($path.'/'.$fname, $class.'\\'.$className );

  }

}