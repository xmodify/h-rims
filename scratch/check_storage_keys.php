<?php
$js = file_get_contents('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js');
preg_match_all('/ngx-webstorage\|[a-zA-Z0-9_\-]+/', $js, $matches);
echo "Webstorage keys in SMT:\n";
print_r(array_unique($matches[0]));
