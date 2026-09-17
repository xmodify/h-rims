<?php
$js = file_get_contents('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js');
echo substr($js, 3668572, 2000);
