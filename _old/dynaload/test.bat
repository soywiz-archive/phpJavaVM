@echo off
cls
bin2c php_dynacall_init.php php_dynacall_init.php.c dynacall_init_php > NUL
\dev\tcc\tiny_impdef.exe php5ts.dll > NUL
\dev\tcc\tcc.exe -shared php_dynacall.c php_dynacall_init.php.c php5ts.def -lkernel32 -o ext\php_dynacall.dll && php test.php