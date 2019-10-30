<?php
 list($t1, $t2) = explode(' ', microtime()); 
 echo  (float)sprintf('%.0f',(floatval($t1)+floatval($t2))*1000);  