<?php
use GlobalData\Client;
require_once __DIR__ . '/../src/Client.php';

$global = new Client();

echo "\n\nisset(\$global->abc)=";
var_export(isset($global->abc));

echo ";\n\nset \$global->abc=";
$global->abc = array(1,2,3);
var_export($global->abc);

echo ";\n\nunset(\$global->abc)\n";
unset($global->abc);
echo "\nnow \$global->abc=";
var_export($global->abc);

echo ";\n\nset $global->abc=";
$global->abc = array(1,2,3);
var_export($global->abc);

echo ";\n\ncas('abc', array(1,2,3), array(5,6,7))=";
var_export($global->cas('abc', array(1,2,3), array(5,6,7)));

echo ";\n\n\now $global->abc=";
var_export($global->abc);
