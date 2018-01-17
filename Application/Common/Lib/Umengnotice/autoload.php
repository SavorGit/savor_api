<?php

function classLoader($class)
{
    //var_dump($class);
    $path = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    //var_dump($path);
    //���Ͱ���һ����srcĿ¼���Բ��úͰ�����һ������д
    $file = dirname(__DIR__) . DIRECTORY_SEPARATOR. $path . '.php';
    //var_dump($file);
    if (file_exists($file)) {
        require_once $file;
    }
}
spl_autoload_register('classLoader');