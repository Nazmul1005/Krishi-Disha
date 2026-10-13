<?php
function sourceUrl($raw): string {
    $url=textInput($raw,500);
    if(!filter_var($url,FILTER_VALIDATE_URL) || !str_starts_with($url,'https://') || parse_url($url,PHP_URL_USER) !== null) throw new InvalidArgumentException('Provide a valid HTTPS reference source.');
    return $url;
}
function validateProposal(string $section,array $data): void {
    sourceUrl($data['source_url'] ?? '');
    if($section==='crop') {
        textInput($data['name'] ?? '',150);
        if(!in_array($data['category'] ?? '',['Grain','Vegetable','Fruit','Fiber','Oilseed','Legume','Cash Crop'],true) || !in_array($data['season'] ?? '',['summer','winter','rainy','all'],true) || !in_array($data['trade_status'] ?? '',['local','export','both'],true)) throw new InvalidArgumentException('Invalid crop category, season or trade status.');
    } elseif($section==='disease') { foreach(['name','symptoms','solution','affected_part'] as $key) textInput($data[$key] ?? '',$key==='name'?200:5000); }
    elseif($section==='recommender') {
        textInput($data['region'] ?? '',150);textInput($data['soil_type'] ?? '',100);
        if(filter_var($data['suitability_score'] ?? '',FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>10]])===false || !in_array($data['season'] ?? '',['summer','winter','rainy','all'],true)) throw new InvalidArgumentException('Suitability must be from 1 to 10 and season must be valid.');
    } elseif($section==='nutrition') {
        textInput($data['preparation_notes'] ?? '',3000);
        $value=$data['retention_percentage'] ?? null;
        if(!is_scalar($value) || !is_numeric($value) || (float)$value<0 || (float)$value>100) throw new InvalidArgumentException('Retention must be from 0 to 100.');
    } else throw new InvalidArgumentException('Unsupported proposal type.');
}
function knowledgeNotice(): string {
    return '<aside class="alert-kd alert-kd-info">'.(($_SESSION['language'] ?? 'bn')==='bn' ? 'কৃষি তথ্য স্থানীয় বিশেষজ্ঞের পরামর্শের বিকল্প নয়। প্রকাশিত তথ্যের উৎস ও পর্যালোচনার তারিখ দেখুন।' : 'Reference information needs local context. Check the source and review date, and consult a qualified local expert.').'</aside>';
}
