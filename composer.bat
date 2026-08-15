@echo off
if not exist composer.phar (
    echo Downloading composer.phar...
    php -r "readfile('https://getcomposer.org/installer');" | php
    php composer.phar install
)
C:\php\8.5.8\php -d xdebug.mode=off composer.phar %*