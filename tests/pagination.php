<?php
$pdo->exec('CREATE TEMPORARY TABLE TEST_LIST(id INT PRIMARY KEY,name VARCHAR(100),status VARCHAR(30),owner_id INT)');
$insert=$pdo->prepare('INSERT INTO TEST_LIST VALUES (?,?,?,?)');for($i=1;$i<=56;$i++)$insert->execute([$i,$i===1?'literal_%':"Row $i",$i%2?'pending':'approved',$i<=55?1:2]);
$_GET=['records_page'=>'2'];$rows=pagedRows($pdo,'SELECT t.* FROM TEST_LIST t WHERE owner_id=? ORDER BY t.id DESC',[1],'records','Records',['name'],true);
check(count($rows)===25 && (int)$rows[0]['id']===30 && (int)end($rows)['id']===6,'SQL pagination preserves sort order and owner scope');
$_GET=['records_page'=>'99999'];$rows=pagedRows($pdo,'SELECT * FROM TEST_LIST WHERE owner_id=? ORDER BY id DESC',[1],'records','Records',['name'],true);
check(count($rows)===5 && !str_contains(listPagers(),'rel="next"'),'Out-of-range pages clamp and terminal pages have no Next link');
$_GET=['records_q'=>'_%'];$rows=pagedRows($pdo,'SELECT * FROM TEST_LIST WHERE owner_id=? ORDER BY id DESC',[1],'records','Records',['name'],true);
check(count($rows)===1 && (int)$rows[0]['id']===1,'List search treats user wildcards as literal text');
$_GET=['records_status'=>'approved'];$rows=pagedRows($pdo,'SELECT * FROM TEST_LIST WHERE owner_id=? ORDER BY id DESC',[1],'records','Records',['name'],true);
check(count($rows)===25 && $GLOBALS['kd_lists']['records']['total']===27,'Status filters run before bounded list fetch');
$_GET=['records_q'=>"' OR 1=1 --"];$rows=pagedRows($pdo,'SELECT * FROM TEST_LIST WHERE owner_id=? ORDER BY id DESC',[1],'records','Records',['name'],true);
check(!$rows,'Hostile list searches remain prepared query values');
$_GET=[];$rows=pagedRows($pdo,'SELECT t.id,t.name AS title,t.status FROM TEST_LIST t WHERE t.owner_id=:owner ORDER BY t.name ASC',[':owner'=>2],'records','Records',['title'],true);
check(count($rows)===1 && (int)$rows[0]['id']===56,'Named parameters and selected sort aliases remain supported');
unset($GLOBALS['kd_lists']);
