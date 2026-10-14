<?php
/** Bound operational lists in SQL. Query/column arguments are application-owned. */
function listQueryValue(string $key,int $max=150): string {
    $raw=$_GET[$key] ?? '';return is_scalar($raw)?mb_substr(trim((string)$raw),0,$max):'';
}
function pagedRows(PDO $pdo,string $sql,array $params,string $key,string $label,array $searchFields=[],bool $hasStatus=false,int $size=25): array {
    if(!preg_match('/^[a-z_]+$/D',$key) || $size<1 || $size>100) throw new LogicException('Invalid list configuration.');
    $sql=rtrim(trim($sql),';');
    if(!array_is_list($params)){$ordered=[];$sql=preg_replace_callback('/:([a-z_][a-z_0-9]*)/i',function($match)use($params,&$ordered){$ordered[]=$params[':'.$match[1]] ?? $params[$match[1]] ?? null;return '?';},$sql);$params=$ordered;}
    $order='';if(preg_match('/\s+ORDER BY\s+([^()]+)$/i',$sql,$match)){
        $order=$match[1];$sql=substr($sql,0,-strlen($match[0]));
        preg_match_all('/([a-z_]+\.[a-z_]+)\s+AS\s+([a-z_]+)/i',$sql,$aliases,PREG_SET_ORDER);
        foreach($aliases as $alias)$order=preg_replace('/\b'.preg_quote($alias[1],'/').'\b/i',$alias[2],$order);
        $order=preg_replace('/\b[a-z_]+\.([a-z_]+)/i','$1',$order);
        if(!preg_match('/^[a-z_0-9`,\s]+$/iD',$order))throw new LogicException('Unsupported list ordering.');
    }
    $where=[];$extra=[];$search=listQueryValue($key.'_q');$status=listQueryValue($key.'_status',30);
    foreach($searchFields as $field) if(!preg_match('/^[a-z_]+$/D',$field)) throw new LogicException('Invalid search column.');
    if($search!=='' && $searchFields){$where[]='('.implode(' OR ',array_map(fn($field)=>"CAST(`$field` AS CHAR) LIKE ?",$searchFields)).')';foreach($searchFields as $field)$extra[]='%'.str_replace(['!','%','_'],['!!','!%','!_'],$search).'%';$where[count($where)-1]=str_replace('LIKE ?',"LIKE ? ESCAPE '!'",end($where));}
    if($hasStatus && $status!==''){$where[]='status=?';$extra[]=$status;}
    $query='SELECT * FROM ('.$sql.') kd_list'.($where?' WHERE '.implode(' AND ',$where):'');
    $arguments=array_merge($params,$extra);$stmt=$pdo->prepare('SELECT COUNT(*) FROM ('.$query.') kd_count');$stmt->execute($arguments);$total=(int)$stmt->fetchColumn();
    $pages=max(1,(int)ceil($total/$size));$raw=listQueryValue($key.'_page',10);$page=max(1,min($pages,ctype_digit($raw)?(int)$raw:1));$offset=($page-1)*$size;
    $stmt=$pdo->prepare($query.($order?' ORDER BY '.$order:'')." LIMIT $size OFFSET $offset");$stmt->execute($arguments);$rows=$stmt->fetchAll();
    $GLOBALS['kd_lists'][$key]=compact('key','label','search','status','searchFields','hasStatus','page','pages','size','total');
    return $rows;
}
function listUrl(string $key,int $page): string {
    $params=[];foreach($_GET as $name=>$value) if(is_scalar($value))$params[$name]=(string)$value;
    $params[$key.'_page']=$page;return '?'.http_build_query($params);
}
function listControls(): string {
    $html='';foreach($GLOBALS['kd_lists'] ?? [] as $list){
        $html.='<details class="list-controls"'.($list['search']!=='' || $list['status']!==''?' open':'').'><summary>'.e($list['label']).' · '.e($list['total']).($list['total']===1?' record':' records').' · Filter</summary><form method="GET" class="filter-bar">';
        foreach($_GET as $key=>$value)if(is_scalar($value) && !in_array($key,[$list['key'].'_q',$list['key'].'_status',$list['key'].'_page','edit','detail'],true))$html.='<input type="hidden" name="'.e($key).'" value="'.e($value).'">';
        if($list['searchFields'])$html.='<label for="'.$list['key'].'-query">Search '.e($list['label']).'</label><input type="search" id="'.$list['key'].'-query" name="'.$list['key'].'_q" value="'.e($list['search']).'" maxlength="150">';
        if($list['hasStatus']){$html.='<label for="'.$list['key'].'-status">Status</label><select id="'.$list['key'].'-status" name="'.$list['key'].'_status"><option value="">All statuses</option>';foreach(listStatuses($list['key']) as $status)$html.='<option value="'.$status.'"'.($list['status']===$status?' selected':'').'>'.e(ucfirst($status)).'</option>';$html.='</select>';}
        $html.='<button class="btn-kd btn-kd-primary">Apply filters</button><a class="btn-kd btn-kd-outline" href="'.e(listResetUrl($list['key'])).'">Reset</a></form></details>';
    }return $html;
}
function listStatuses(string $key): array {
    return match($key){
        'users'=>['pending','approved','suspended'],
        'produce','products'=>['pending','available','sold'],
        'farms','tours'=>['pending','active','inactive'],
        'proposals'=>['pending','approved','rejected'],
        'payments'=>['pending','completed','failed','refunded'],
        'support'=>['open','resolved'],
        'food'=>['pending','preparing','delivered','cancelled'],
        'orders','sales'=>['pending','confirmed','delivered','cancelled'],
        default=>['pending','confirmed','completed','cancelled']
    };
}
function listResetUrl(string $key): string {
    $params=[];foreach($_GET as $name=>$value)if(is_scalar($value) && !in_array($name,[$key.'_q',$key.'_status',$key.'_page'],true))$params[$name]=$value;
    return '?'.http_build_query($params);
}
function listPagers(): string {
    $html='';foreach($GLOBALS['kd_lists'] ?? [] as $list){$html.='<nav class="pagination" aria-label="'.e($list['label']).' pages"><span class="pagination-summary">'.e($list['label']).': '.$list['total'].($list['total']===1?' record':' records').' · Page '.$list['page'].' of '.$list['pages'].'</span><div>';
        if($list['page']>1)$html.='<a rel="prev" href="'.e(listUrl($list['key'],$list['page']-1)).'">Previous</a> ';
        if($list['page']<$list['pages'])$html.='<a rel="next" href="'.e(listUrl($list['key'],$list['page']+1)).'">Next</a>';
        $html.='</div></nav>';
    }return $html;
}
